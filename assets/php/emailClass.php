<?php
##################################################################################
#
# File				: sec.class.inc.php - Main Class File of simpleEmailClass
# Class Title		: simpleEmailClass 
# Class Description	: This class is used to produce HTML format emails, with
#					  the ability to attach files and embed images.
# Class Notes		: Please Let Me Know if you have any problems at all with this
#					  Class.
# Copyright			: 2007
# Licence			: http://www.gnu.org/copyleft/gpl.html GNU var License
#
# Author 			: Mark Davidson <design@fluxnetworks.co.uk> 
#					  <http://design.fluxnetworks.co.uk>
# Created Date     	: 27/01/2007
# Last Modified    	: 30/01/2007
#
##################################################################################
/***************************
Setup
***************************/
//url to redirect the user if this script is not requested via ajax.
$redirectURL = 'http://'.$_SERVER['HTTP_HOST'].'/contact/success'; 

//comma separated emails that get alerted with suspicious activity. 
$adminEmails = "email@address.com, another@emailaddress.com" 
//A from address to send suspiciuous activity alerts from.
$alertSender =  "do-not-reply@sitename.com"



// Check the referrer or the honeypot for malicious intent
if (!preg_match("/^http:\/\/".$_SERVER['HTTP_HOST']."/", $_SERVER['HTTP_REFERER']) || $_REQUEST['hp']) {
	// Fake the normal error response
	$y = array();
	$y['success'] = 0;
	$y['msg'] = 'Error sending email.';
	echo json_encode($y);
	
	// Build a descriptive array
	$x = array();
	$x['referer'] = $_SERVER['HTTP_REFERER'];
	$x['host'] = 'http://'.$_SERVER['HTTP_HOST'];
	$x['honeypot'] = $_REQUEST['hp'];
	
	// Shoot the admins a heads up
	$to = $adminEmails;
	$subject = "Hacker Alert - {$_SERVER['HTTP_HOST']}";
	$message = "Someone Attempted to hack the following file:\n {$_SERVER['HTTP_HOST']}/assets/php/emailClass.php\n\nBelow is the offending info:\n".json_encode($x);
	$from = $alertSender;
	$headers = "From: $from";
	mail($to,$subject,$message,$headers);
	exit;
}

class sec {
	var $secVersion = '1.0';
	var $to = '';
	var $Cc = array();
	var $Bcc = array();
	var $subject = '';
	var $message = '';
	var $attachment = array();
	var $embed = array();
	var $charset = 'ISO-8859-1';
	var $emailboundary = '';
	var $emailheader = '';
	var $textheader = '';
	var $errors = array();
	
	function sec($toname, $toemail, $fromname, $fromemail) {
		$this->emailboundary = uniqid(time());
		$this->to = "{$toname} <".$this->validateEmail($toemail).">";
		$email = $this->validateEmail($fromemail);
		$this->emailheader .= "From: {$fromname} <{$email}>\r\n";
	}
	
	function validateEmail($email) {
		if (!preg_match('/^[A-Z0-9._%-]+@(?:[A-Z0-9-]+\\.)+[A-Z]{2,4}$/i', $email)){
			$x = array();
			$x['success'] = 0;
			$x['msg'] = "The email '{$email}' is not valid.";
			echo json_encode($x);
			exit;
		}	
		return $email;
	}
	
	function Cc($email) {
		$this->Cc[] = $this->validateEmail($email); 
	}
	
	function Bcc($email) { 
		$this->Bcc[] = $this->validateEmail($email); 
	}
	
	function buildHead($type) {
		$count = count($this->$type);
		if($count > 0) {
			$this->emailheader .= "{$type}: ";
			$array = $this->$type;
			for($i=0; $i < $count; $i++) {
				if($i > 0) $this->emailheader .= ',';
				$this->emailheader .= $this->validateEmail($array[$i]);
			}
			$this->emailheader .= "\r\n";
		}
	}
	
	function buildMimeHead() {		
		$this->buildHead('Cc');
		$this->buildHead('Bcc');
		
		$this->emailheader .= "X-Mailer: simpleEmailClass v{$this->secVersion}\r\n";
		$this->emailheader .= "MIME-Version: 1.0\r\n";
	}
	
	function buildMessage($subject, $message = '') {
		$textboundary = uniqid(time());
		$this->subject = strip_tags(trim($subject));
		
		$this->textheader = "Content-Type: multipart/alternative; boundary=\"$textboundary\"\r\n\r\n";
		$this->textheader .= "--{$textboundary}\r\n";
		$this->textheader .= "Content-Type: text/plain; charset=\"{$this->charset}\"\r\n";
		$this->textheader .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
		$this->textheader .= strip_tags($message)."\r\n\r\n";
		$this->textheader .= "--$textboundary\r\n";
		$this->textheader .= "Content-Type: text/html; charset=\"$this->charset\"\r\n";
		$this->textheader .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
		$this->textheader .= "<html>\n<body>\n{$message}\n</body>\n</html>\r\n\r\n";
		$this->textheader .= "--{$textboundary}--\r\n\r\n";
	}
	
	function mime_type($file) {
		return (function_exists('mime_content_type')) ? mime_content_type($file) : trim(exec('file -bi '.escapeshellarg($file)));
	}
	
	function attachment($file) {
		if(is_file($file)) {
			$basename = basename($file);
			$attachmentheader = "--{$this->emailboundary}\r\n";
			$attachmentheader .= "Content-Type: ".$this->mime_type($file)."; name=\"{$basename}\"\r\n";
			$attachmentheader .= "Content-Transfer-Encoding: base64\r\n";
			$attachmentheader .= "Content-Disposition: attachment; filename=\"{$basename}\"\r\n\r\n";
			$attachmentheader .= chunk_split(base64_encode(fread(fopen($file,"rb"),filesize($file))),72)."\r\n";
			
			$this->attachment[] = $attachmentheader;
		} else {
			die('The File '.$file.' does not exsist.');
		}
	}
	
	function embed($file) {
		if(is_file($file)) {
			$basename = basename($file);
			$fileinfo = pathinfo($basename);
			$contentid = md5(uniqid(time())).".".$fileinfo['extension'];
			$embedheader = "--{$this->emailboundary}\r\n";
			$embedheader .= "Content-Type: ".$this->mime_type($file)."; name=\"{$basename}\"\r\n";
			$embedheader .= "Content-Transfer-Encoding: base64\r\n";
			$embedheader .= "Content-Disposition: inline; filename=\"{$basename}\"\r\n";
			$embedheader .= "Content-ID: <{$contentid}>\r\n\r\n";
			$embedheader .= chunk_split(base64_encode(fread(fopen($file,"rb"),filesize($file))),72)."\r\n";
			
			$this->embed[] = $embedheader;
						
			return "<img src=3D\"cid:{$contentid}\">";
		} else {
			die('The File '.$file.' does not exsist.');
		}
	}
	
	function sendmail() {
		if(!strlen($_POST['hp']) || $_POST['hp'] == ''){
			$this->buildMimeHead();

			$header = $this->emailheader;

			$attachcount = count($this->attachment);
			$embedcount = count($this->embed);

			if($attachcount > 0 || $embedcount > 0) {
				$header .= "Content-Type: multipart/mixed; boundary=\"{$this->emailboundary}\"\r\n\r\n";
				$header .= "--{$this->emailboundary}\r\n";
				$header .= $this->textheader;

				if($attachcount > 0) $header .= implode("",$this->attachment);
				if($embedcount > 0) $header .= implode("",$this->embed);
				$header .= "--{$this->emailboundary}--\r\n\r\n";
			} else {
				$header .= $this->textheader;
			}

			return mail($this->to, $this->subject, $this->message, $header);
		}
		
	}
}

/*
Implementation
*/

$returnArr = array();
$returnArr['success'] = 0;
$returnArr['msg'] = 'Error Initializing.';

$requiredFields = array('to', 'from', 'subject', 'message');
$formData = getFormData($requiredFields);

if($formData['valid']){
	#To - Name, To - Email, From - Name, From - Email
	$sec = new sec('', $formData['fields']['to'], $formData['fields']['name'], $formData['fields']['from']);
	
	#build the message with the message title and message content
	$sec->buildMessage($formData['fields']['subject'], $formData['fields']['message']);
	
	#build and send the email
	if($sec->sendmail()) {
		$returnArr['success'] = 1;
		$returnArr['msg'] = 'Success!';
	} else {
		$returnArr['success'] = 0;
		$returnArr['msg'] = 'Error sending email.';
	}
	
}else{
	$returnArr['success'] = 0;
	$returnArr['msg'] = 'Fields not valid.';
}
function isAjax() {
	return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
		($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
}
if(isAjax()){
	echo json_encode($returnArr);
} else{
	
	header( 'location: ' . $redirectURL );
}


function getFormData($requiredFields){
       $formData = array();
       $formData['valid'] = true;
       $formData['fields'] = array();
       $formData['notValidFields'] = array();
       
       $formData['fields']['name'] = $_REQUEST['name'] ? $_REQUEST['name'] : '';
       
       for($a = 0; $a < count($requiredFields); $a++){
               $field = $requiredFields[$a];
               if(isset($_POST[$field])){
                       $value = $_POST[$field];
                       if(empty($value)){
                               $formData['valid'] = false;
                               $formData['notValidFields'][] = $field;
                       }else{
                               $formData['fields'][$field] = $value;
                       }
               }else{
                       $formData['valid'] = false;
                       $formData['notValidFields'][] = $field;
               }
       }
       return $formData;
}