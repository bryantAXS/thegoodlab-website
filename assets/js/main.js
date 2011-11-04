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

  $(window).bind('resize',function(){
    var main_container_padding = $(this).height() - $('#main-container').height();
    $('#main-container').css({
      paddingBottom: main_container_padding
    })
    var white_col_container_height = $('#white-col-container').height() + $(this).height() - $('#white-col-container').outerHeight();
    $('#white-col-container').css({
      height: white_col_container_height
    });
  });

  $(window).trigger('resize');
  
  $('header ul#dots li img').bind('mouseenter',function(){
    $(this).animate({opacity:'0.95'});
  }).bind('mouseleave',function(){
    $(this).animate({opacity:'0.7'});
  })
  
  //init galleries drawn on page
  // var $galleries = $('.work-gallery')
  // if($galleries.length){
  //   var work_gallery = new Work_Gallery();
  //   work_gallery.init_galleries($galleries);
  // }
  
  // //paginate work examples
  // if($('#work-offset').length){
  //   var work_pagination = new Work_Pagination();
  //   work_pagination.init();
  // }
  
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
















