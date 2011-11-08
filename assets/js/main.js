/* 
	Author: Bryant Hughes 
*/

$(document).ready(function(){
  
  // $('.home-work-sample-container').bind('mouseenter',function(){
  //   var $el = $(this);
  //   $el.find('div').animate({opacity: 1},500);
  // }).bind('mouseleave',function(){
  //   var $el = $(this);
  //   $el.find('div').animate({opacity: 0},500);
  // });
  
  //init header dots
  $('header ul#dots li img').bind('mouseenter',function(){
    $(this).animate({opacity:'0.95'});
  }).bind('mouseleave',function(){
    $(this).animate({opacity:'0.7'});
  });
 
  //init work galleries
  if($('.work-gallery').length){
    
    $('.work-gallery').each(function(){
      
      $gallery = $(this);
      $controls_container = $gallery.prev().find('div');
      $next = $gallery.prev().find('.button-next');

      $gallery.cycle({
        activePagerClass: 'active'
        ,fx:'fade'
        ,pager: $controls_container
        ,speed:  600 
        ,timeout: 0
        ,next: $next
      });
        
    });
  }

  //resize content-container, so white bar goes to the bottom of the page
  $(window).bind('resize',function(){
    if($(window).width() < 1113){
      $('#content-container').height('auto');
    }else{
      if($('#content-container').height() < $(document).height()){
        $('#content-container').height($(document).height());
      }
    }
  });
  if($(window).width() < 1113){
    $('#content-container').height('auto');
  }else{
    if($('#content-container').height() < $(document).height()){
      $('#content-container').height($(document).height());
    }
  }

  //toggles on about page
  $('#about-content-container-right a.services-title').bind('click',function(){
    $el = $(this);
    $h3 = $el.find('h3:eq(0)');
    if($el.next('ul').hasClass('open')){
      var new_html = $h3.html().replace('-','+');
      $h3.html(new_html);
      $el.next().removeClass('open').slideUp(function(){
        $(this).addClass('closed');
      });
    }else{
      var new_html = $h3.html().replace('+','-');
      $h3.html(new_html);
      $el.next().addClass('open').slideDown(function(){
        $(this).removeClass('closed');
      });
    }
  });
  
  //lists in article content
  $('#article-content-container ol li, #article-content-container ul li').wrapInner('<span class="white"></span>');
  
});
















