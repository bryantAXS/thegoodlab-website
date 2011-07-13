/* 
	Author: Bryant Hughes 
*/

var Work_Gallery = function(){
  
}

Work_Gallery.prototype.init_galleries = function($els){
  
  $els.each(function(){
    var $el = $(this);
    var $parent = $el.parent();
    var $gallery = $el.cycle({ 
        fx:     'fade', 
        speed:   500, 
        timeout: false, 
        pause:   1 
    });
    $parent.find('.button-next').bind('click',function(){
      $gallery.cycle('next');
      $next_dot = $parent.find('.dot.active').next('.dot').length ? $parent.find('.dot.active').next('.dot') : $parent.find('.dot:eq(0)');
      $parent.find('.dot.active').removeClass('active');
      $next_dot.addClass('active');
    });
    $parent.find('.dot').bind('click',function(){
      var $el = $(this);
      var index = Number($el.attr('rel'));
      $parent.find('.dot.active').removeClass('active');
      $el.addClass('active');
      $gallery.cycle(index);
    });
  });
  
}

// controls the pagination on the work page
var Work_Pagination = function(){
  var self = this;
  
  //get the offset number
  self.$offset = $('#work-offset');
  self.offset = Number(self.$offset.val());
  self.first_id = $('#work-first-id').val() ? $('#work-first-id').val() : false;
  
  //get the total number of results we are going to be moving through
  self.total_results = Number($('#work-total-results').val());
}
Work_Pagination.prototype.init = function(){
  
  var self = this;
  
  //bind to button
  $('#next-project').live('click',function(){
    self.increment_offset();
    self.get_project(self.offset);
  });
  
}
//handles the icrementing and resetting of the offset
Work_Pagination.prototype.increment_offset = function(){
  var self = this;
  if((self.total_results - 1) == self.offset){
    self.offset = 0;
  }else{
    self.offset += 1;
  }
}
//ajax call to get the project data
Work_Pagination.prototype.get_project = function(offset){
  
  var self = this;
  
  //build url
  var url = '/lib/get_work/' + offset;
  if(self.first_id){
    //if we got sent here via a permalink, we don't want the next item we go to to be the same page, so lets skip it
    url += '/' + self.first_id;
    self.first_id = false;
  }
  
  $.ajax({
    url: url,
    type: 'POST',
    dataType: 'json',
    complete: function(xhr, textStatus) {
      //called when complete
    },
    success: function(data, textStatus, xhr) {
      //called when successful
      var new_markup = $('#work-template').tmpl(data);
      self.swap_projects(new_markup);
    },
    error: function(xhr, textStatus, errorThrown) {
      //called when there is an error
    }
  });
}
//swap out the projects
Work_Pagination.prototype.swap_projects = function(new_markup){
  
  var $new_markup = $(new_markup);
  $new_markup.css({'opacity':'0'});
  
  //hack to get first dot active
  $new_markup.find('.dot:eq(0)').addClass('active');
  
  var $new_galleries = $new_markup.find('.work-gallery');
  
  var work_gallery = new Work_Gallery();
  //work_gallery.init_galleries($new_galleries);
  
  var $container = $('.work-container');
  $container.animate({'opacity':'0'},{
    duration:500
    ,complete:function(){
      $container.remove();
      $('#content-container').append($new_markup);
      $new_markup.animate({'opacity':'1'},{
        duration: 500
        ,complete:function(){
          work_gallery.init_galleries($new_galleries);
        }
      });
    }
  });
}

$(document).ready(function(){
  
  $('.home-work-sample-container').bind('mouseenter',function(){
    var $el = $(this);
    $el.find('div').animate({opacity: 1},500);
  }).bind('mouseleave',function(){
    var $el = $(this);
    $el.find('div').animate({opacity: 0},500);
  });
  
  $('header ul#dots li img').bind('mouseenter',function(){
    $(this).animate({opacity:'0.95'});
  }).bind('mouseleave',function(){
    $(this).animate({opacity:'0.7'});
  })
  
  //init galleries drawn on page
  var $galleries = $('.work-gallery')
  if($galleries.length){
    var work_gallery = new Work_Gallery();
    work_gallery.init_galleries($galleries);
  }
  
  //paginate work examples
  if($('#work-offset').length){
    var work_pagination = new Work_Pagination();
    work_pagination.init();
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
















