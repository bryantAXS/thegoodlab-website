/* 
	Author: Bryant Hughes 
*/

$(document).ready(function(){
  
  //triggering events for iphone/ipad -- an alternative to mouseenter/mouseleave
  
  if( ! Modernizr.cssanimations){
    
    $('.home-work-samples-content').css({'opacity': 0});

    var cb = function(){

      var $el = $('.home-work-sample-container.on').length ? $('.home-work-sample-container.on') : $('.home-work-sample-container:eq(0)');

      if($el.hasClass('on')){
        
        $next_el = $el.next('.home-work-sample-container').length ? $el.next('.home-work-sample-container') : $('.home-work-sample-container:eq(0)');
        
        $('.home-work-sample-container.on').find('.home-work-samples-content').animate({'opacity':0},500,function(){
          $(this).parent().removeClass('on');
        })

        $next_el.addClass('on');
        $next_el.find('.home-work-samples-content').animate({'opacity':'.85'},500);

      }else{
        
        $('.home-work-sample-container.on').find('.home-work-samples-content').animate({'opacity':0},500,function(){
          $(this).parent().removeClass('on');
        });

        $el.addClass('on');
        $el.find('.home-work-samples-content').animate({'opacity':'.85'},500);
      
      }

    }
  
    setInterval(cb, 4000);

  }else if(Modernizr.touch){
    
    var cb = function(){

      var $el = $('.home-work-sample-container.on').length ? $('.home-work-sample-container.on') : $('.home-work-sample-container:eq(0)');

      if($el.hasClass('on')){
        
        $next_el = $el.next('.home-work-sample-container').length ? $el.next('.home-work-sample-container') : $('.home-work-sample-container:eq(0)');
        $('.home-work-sample-container.on').removeClass('on');
        $next_el.addClass('on');

      }else{
        $('.home-work-sample-container.on').removeClass('on');
        $el.addClass('on');
      }

    }

    setInterval(cb, 4000);

  }
  
  
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
  
  
  //colors insdide ol and ul lists in article content
  $('#article-content-container ol li, #article-content-container ul li').each(function(){
    
    $el = $(this);

    if( ! $el.find('p').length){
      $el.wrapInner('<p class="white"></p>');
    }else{
      $el.find('p').addClass('white');
    }

  });

  
  //honey pot for comment form
  $('#comment_form').bind('submit',function(){
    
    if($('#honeepot').val() != ""){
      return false;
    }

  });

  $('.fancybox').fancybox();

  SyntaxHighlighter.defaults['tab-size'] = 2;
  SyntaxHighlighter.defaults['toolbar'] = true;
  SyntaxHighlighter.all()
  
});
















