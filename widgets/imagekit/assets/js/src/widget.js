(function(window, document, $) {
  
  function ImageKitAPI(options) {
    var self     = this;
    var settings = options;
    
    function api(command, callback, data, method) {
      method   = method || 'GET';
      data     = data || {};
      callback = callback || function(){};
      
      var url = settings.api + command;
      
      return $.ajax({
        url      : url,
        dataType : 'json',
        method   : method,
        data     : data,
        success  : function(response) {
          callback(response);
        },
        error    : function(response) {
          console.warn(response);
        }
      });
    }
    
    function status(callback) {
      return api("status", callback);
    }
    
    function clear(callback) {
      return api("clear", callback);
    }
    
    function create(callback) {
      return api("create", callback);
    }
    
    // function index(step, complete) {
    //   step     = step || function(){};
    //   complete = complete || function(){};
    //   
    //   api("index", function (response) {
    //     var i = 0;
    //     var pageUrls = response.data;
    //     
    //     running(true);
    //     
    //     var triggerPageLoad = function() {
    //       step({ current: i, total: pageUrls.length, url: pageUrls[i] });
    //       $.ajax({
    //         url: pageUrls[i],
    //         method: 'HEAD',
    //         complete: function() {
    //           if (++i <= pageUrls.length) {
    //             triggerPageLoad();
    //           } else {
    //             running(false);
    //             complete({ total: pageUrls.length });
    //           }
    //         }
    //       });
    //     };
    //     
    //     triggerPageLoad();
    //   });
    // }
    
    return {
      status : status,
      clear  : clear,
      create : create,
    };
  }
  
  function Widget(options) {
    var settings       = options,
        imagekit       = new ImageKitAPI(ImageKitSettings),
        infoElm        = document.querySelector(".js-imagekit-info"),
        clearLink      = document.querySelector('[href="#imagekit-action-clear"]'),
        createLink     = document.querySelector('[href="#imagekit-action-create"]'),
        createLinkIcon = createLink.getElementsByTagName("i")[0],
        progressElm    = document.querySelector(".js-imagekit-progress"),
        _running       = false,
        _data;
    
    
    function running(running) {
      
      if(running === undefined) {
        return _running;
      } else {
        _running = running;
        return self;
      }
    }
    
    function progress(show) {
      if(show) progressElm.removeAttribute("value");
      progressElm.classList[show ? "remove" : "add"]("is-hidden");
    }
    
    function clear(e) {
      e.preventDefault();
      
      if(running()) return;
      
      var clear = window.confirm(window.ImageKitSettings.translations['imagekit.widget.clear.confirm']);
      
      if(clear) {
        running(true);
        progress(true);
        clearLink.classList.add("imagekit-action--disabled");
        
        imagekit.clear(function(result) {
          running(false);
          update(result);
          progress(false);
          clearLink.classList.remove("imagekit-action--disabled");
        });
      }
    }
    
    function create(e) {      
      e.preventDefault();
      
      if(running()) {
        running(false);
        createLinkIcon.className = "icon icon-left fa fa-play-circle-o";
        return;
      } else {
        running(true);
        createLinkIcon.className = "icon icon-left fa fa-stop-circle-o";
        clearLink.classList.add("imagekit-action--disabled");
      }
      
      progress(true);
      
      function doCreate() {
        imagekit.create(function(result) {
          update(result);
          if(result.data.pending > 0 && running()) {
            doCreate();
          } else {
            running(false);
            progress(false);
            createLinkIcon.className = "icon icon-left fa fa-play-circle-o";
            clearLink.classList.remove("imagekit-action--disabled");
          }
        });
      }
      
      doCreate();
    }
    
    function update(result) {
      _data = result.data;
      
      if (_data.pending > 0 || _data.created > 0) {
        var val = (1 - _data.pending / (_data.pending + _data.created));
        progressElm.setAttribute("value", val);
      } else {
        progressElm.removeAttribute("value");
      }
      
      document.querySelector(".js-imagekit-created").innerHTML = _data.created;
      document.querySelector(".js-imagekit-pending").innerHTML = _data.pending;
      
    }
    
    function updateOptions() {
      
    }
    
    function status() {
      if(!running()) {
        imagekit.status(function(result) {
          update(result);
        });
      }
    }
    
    clearLink.addEventListener("click", clear);
    createLink.addEventListener("click", create);
    
    return {
      status : status,
    };
  }
  
  $(function() {    
    var widget = new Widget();
    widget.status();
  });
  
  
})(window, document, jQuery);
