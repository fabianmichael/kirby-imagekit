(function(window, document, $) {

/* =====  Utility Functions  ================================================ */
  
  function i18n(key) {
    var str = window.ImageKitSettings.translations[key];
    if(str !== undefined) {
      return str;
    } else {
      return '[' + key + ']';
    }
  }
  
  function arrayUnique(array) {
    var a = array.concat();
    for(var i=0; i<a.length; ++i) {
      for(var j=i+1; j<a.length; ++j) {
        if(a[i] === a[j])
          a.splice(j--, 1);
      }
    }
    return a;
  }
  
/* =====  ImageKit API  ===================================================== */
  
  function ImageKitAPI(options) {
    var self       = this,
        settings   = $.extend({
          error: function(response) {
            console.error(response.message);
          },
        }, options),
        _running   = false,
        _cancelled = false;
        
    var ACTION_CREATE = "create",
        ACTION_CLEAR  = "clear",
        ACTION_INDEX  = "index";
    
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
        error    : function(xhr, status, error) {
          settings.error(xhr.responseJSON);
        }
      });
    }
    
    function start(action) {
      _running = action;
    }
    
    function stop() {
      _running = false;
    }
    
    function running(running) {
      if(running === undefined) {
        return _running;
      } else {
        return (_running === running);
      }
    }
    
    function status(callback) {
      return api("status", callback);
    }
    
    function clear(callback) {
      reset();
      start(ACTION_CLEAR);
      return api(ACTION_CLEAR, function(response) {
        stop(ACTION_CLEAR);
        callback(response.data);
      });
    }  
    
    function create(step, complete) {
      reset();
      start(ACTION_CREATE);
      
      step      = step || function(){};
      complete  = complete || function(){};
      
      function doCreate() {
        api(ACTION_CREATE, function (response) {
          if (response.data.pending > 0 && !cancelled()) {
            step(response.data);
            doCreate();
          } else {
            stop(ACTION_CREATE);
            complete(response.data);
          }
        });
      }
      
      doCreate();
    }
    
    function index(step, complete, error) {
      reset();
      start(ACTION_INDEX);
          
      step      = step || function(){};
      complete  = complete || function(){};
      error     = error || function(){};
      
      api(ACTION_INDEX, function (response) {
        var i = 0;
        var pageUrls = response.data;
        
        var triggerPageLoad = function() {
          $.ajax({
            url: pageUrls[i],
            headers: {
              "X-ImageKit-Indexing": 1,
            },
            success: function(response) {
              if (response.data.links.length > 0) {
                pageUrls = arrayUnique(pageUrls.concat(response.data.links));
              }
              if (++i >= pageUrls.length || cancelled()) {
                stop(ACTION_INDEX);
                complete({ total: pageUrls.length, status: response.data.status });
              } else {
                step({ current: i, total: pageUrls.length, url: pageUrls[i], status: response.data.status });
                triggerPageLoad();
              }
            },
            error: function(response) {
              error(response.responseJSON);
            }
          });
        };
        
        triggerPageLoad();
      });
    }
    
    function cancel() {
      _cancelled = true;
    }
    
    function cancelled() {
      return _cancelled;
    }
    
    function reset() {
      _running   = false;
      _cancelled = false;
    }
    
    return {
      status    : status,
      clear     : clear,
      create    : create,
      index     : index,
      cancel    : cancel,
      cancelled : cancelled,
      running   : running,
      reset     : reset,
    };
  }
  
/* =====  Progress Bar  ===================================================== */  

  function ProgressBar() {
    
    var progressElm     = document.querySelector(".js-imagekit-progress"),
        progressTextElm = document.querySelector(".js-imagekit-progress-text"),
        _visible        = false,
        self            = this,
        _public         = {};
        
    function toggle(show) {
      if (show === _visible) return self;
      progressElm.classList[show ? "remove" : "add"]("is-hidden");
      _visible = show;
      return _public;
    }
    
    function disable() {
      progressElm.classList.add("is-disabled");
      return _public;
    }
    
    function enable() {
      progressElm.classList.remove("is-disabled");
      return _public;
    }
    
    function show() {
      return toggle(true);
    }
    
    function hide() {
      return toggle(false);
    }
    
    function value(value) {
      if (value !== null && value !== false) {
        progressElm.setAttribute("value", value);
      } else {
        progressElm.removeAttribute("value");
      }
      return _public;
    }
    
    function text(msg) {
      if (msg) {
        progressTextElm.innerHTML = msg;
        return _public;
      } else {
        return progressTextElm.innerHTML;
      }
    }
    
    _public = {
      show    : show,
      hide    : hide,
      value   : value,
      text    : text,
      enable  : enable,
      disable : disable,
    };
    
    return _public;
  }
  
  
/* =====  Actions  ========================================================== */
  
  function Actions() {
    
    var _public = {},
        actions = {
          clear: {
            element: $('[href="#imagekit-action-clear"]'),
            icon:    $('[href="#imagekit-action-clear"] i'),
          },
          create: {
            element: $('[href="#imagekit-action-create"]'),
            icon:    $('[href="#imagekit-action-create"] i'),
          }
        };        
        
    function disable(action) {
      if(action) {
        actions[action].element.addClass("imagekit-action--disabled");
      } else {
        $.each(actions, function() { this.element.addClass("imagekit-action--disabled"); });
      }
      return _public;
    }
    
    function enable(action) {
      if(action) {
        actions[action].element.removeClass("imagekit-action--disabled");
      } else {
        $.each(actions, function() { this.element.removeClass("imagekit-action--disabled"); });
      }
      return _public;
    }
    
    function icon(action, oldClass, newClass) {
      
      var elm = actions[action].icon;
      
      elm.removeClass(oldClass);
      elm.addClass(newClass);   
      
      return _public;
    }
    
    function register(action, callback) {
      actions[action].element.click(function(e) {
        e.preventDefault();
        callback();
      });
      return _public;
    }
    
    _public = {
      disable  : disable,
      enable   : enable,
      icon     : icon,
      register : register,
    };
    
    return _public;
  }
  
  
/* =====  ImageKit Widget  ================================================== */
  
  function Widget(options) {
    var settings        = options,
        api             = new ImageKitAPI($.extend(ImageKitSettings, {
          error: function(response) {
            error(response.message);
          }
        })),
        actions         = new Actions(),
        
        infoElm         = document.querySelector(".js-imagekit-info"),
          
        
        createdElm      = document.querySelector(".js-imagekit-created"),
        pendingElm      = document.querySelector(".js-imagekit-pending"),
        
        progress        = new ProgressBar();
    
/* -----  Internal Interface Methods  --------------------------------------- */
    
    function updateStatus(status) {
      createdElm.innerHTML = status.created;
      pendingElm.innerHTML = status.pending;
    }
    
    function error(message, onClose) {
      onClose = onClose || false;
      actions.disable();
      progress.disable();
      
      var $overlay = $("<div/>").addClass("imagekit-modal");
      $overlay.append('<i class="imagekit-error-icon">'); // fa  fa-exclamation-triangle  fa-2x
      $overlay.append($('<p/>').html(message));
      $("#imagekit-widget").append($overlay);

      if(onClose) {
        $overlay.append($('<a href="#" class="btn btn-rounded">OK</a>').click(function() {
          $overlay.remove();
          api.reset();
          progress.hide();
          actions.enable().icon("create", "fa-stop-circle-o", "fa-play-circle-o");
          status();
          onClose();
        }));
      }
    }

    function confirm(message, onClose) {
      var $overlay = $("<div/>").addClass("imagekit-modal"),
          esc,
          close;

      $overlay.append($('<p/>').html(message));
      $("#imagekit-widget").append($overlay);

      esc = function(e) {
        if("key" in e ? (e.key == "Escape" || e.key == "Esc") : (e.keyCode == 27)) {
          close(false);
        } else if ("key" in e ? e.key == "Enter" : e.key == 13) {
          close(true);
        }
      };

      close = function(result) {
          $overlay.remove();
          onClose(result);
          document.removeEventListener("keydown", esc);
      };

      var $buttons = $('<p class="imagekit-modal-buttons"/>');
      $buttons.append($('<a href="#" class="btn btn-rounded">' + i18n('cancel') + '</a>').click(function() { close(false); } ));
      $buttons.append("&nbsp;&nbsp;&nbsp;");
      $buttons.append($('<a href="#" class="btn btn-rounded">' + i18n('ok') + '</a>').click(function() { close(true); } ));
      $overlay.append($buttons);

      document.addEventListener("keydown", esc);
    }

/* -----  Widget Actions  --------------------------------------------------- */  

    function status() {
      if(api.running()) return;
      
      api.status(function(result) {
        updateStatus(result.data);
      });
    }

    function clear() {    
      if(api.running()) return;


      confirm(i18n('imagekit.widget.clear.confirm'), function(confirmed) {
        if(confirmed) {
          actions.disable();
          
          progress
            .value(false)
            .text(i18n("imagekit.widget.progress.clearing"))
            .show();
          
          api.clear(function(status) {
            progress.hide();
            actions.enable();
            updateStatus(status);
          });
        }
      });
      
      // if(window.confirm(i18n('imagekit.widget.clear.confirm'))) {
      //   actions.disable();
        
      //   progress
      //     .value(false)
      //     .text(i18n("imagekit.widget.progress.clearing"))
      //     .show();
        
      //   api.clear(function(status) {
      //     progress.hide();
      //     actions.enable();
      //     updateStatus(status);
      //   });
      // }
    }
    
    function index(callback) {
      callback = callback || function(){};      
      
      progress.text(i18n("imagekit.widget.progress.scanning"));
        
      api.index(function (result) {
        // step
        progress
          .value(result.current / result.total)
          .text(i18n("imagekit.widget.progress.scanning") + " " + result.current + "/" + result.total);
        
        updateStatus(result.status);
          
      }, function (result) {
        // complete  
        progress
          .value(1)
          .text(i18n("imagekit.widget.progress.scanned"));
          
        updateStatus(result.status);      
        callback();
      }, function (result) {
        // error
        error(result.message, function() {
        });
      });
    }
    
    function create(callback) {
      callback = callback || function(){};      
      
      progress
        .value(false)
        .text(i18n('imagekit.widget.progress.creating'));
        
      api.create(function (result) {
        // step
        var total = result.pending + result.created;
        progress.value(result.created / total);
        updateStatus(result);
      }, function (result) {
        // complete
        progress.value(1);        
        updateStatus(result);
        callback();
      });
    }
    
    function run() {
      
      actions
        .disable("clear")
        .icon("create", "fa-play-circle-o", "fa-stop-circle-o");
        
      progress
        .value(false)
        .show();
        
      function complete() {
        progress.hide();
        
        actions
          .enable()
          .icon("create", "fa-stop-circle-o", "fa-play-circle-o");
      }
      
      if (ImageKitSettings.discover) {
        index(function() {
          if(!api.cancelled()) {
            create(complete);
          } else {
            complete();
          }
        });
      } else {
        create(complete);
      }
    }
    
    function stop() {
      if (api.running("index") || api.running("create")) {
        actions.disable();
        progress
          .value(false)
          .text(i18n('imagekit.widget.progress.cancelling'));
        api.cancel();
        return;
      }
    }
    
    actions.register("clear", clear);
    actions.register("create", function() {
      if (!api.running()) {
        run();
      } else {
        stop();
      }
    });
    
    return {
      status : status,
    };
  }
  
  $(function() {    
    var widget = new Widget();
    widget.status();
  });
  
  
})(window, document, jQuery);
