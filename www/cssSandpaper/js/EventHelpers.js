/*******************************************************************************
 * This notice must be untouched at all times.
 *
 * This javascript library contains helper routines to assist with event 
 * handling consinstently among browsers
 *
 * EventHelpers.js v.1.3 available at http://www.useragentman.com/
 *
 * released under the MIT License:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 *******************************************************************************/
var EventHelpers = new function(){
    var me = this;
    
    var safariTimer;
    var isSafari = /WebKit/i.test(navigator.userAgent);
    var globalEvent;
	
	me.init = function () {
		if (me.hasPageLoadHappened(arguments)) {
			return;	
		}
		
		if (document.createEventObject){
	        // dispatch for IE
	        globalEvent = document.createEventObject();
	    } else 	if (document.createEvent) {
			globalEvent = document.createEvent("HTMLEvents");
		} 
		
		me.docIsLoaded = true;
	}
	
    /**
     * Adds an event to the document.  Examples of usage:
     * me.addEvent(window, "load", myFunction);
     * me.addEvent(docunent, "keydown", keyPressedFunc);
     * me.addEvent(document, "keyup", keyPressFunc);
     *
     * @author Scott Andrew - http://www.scottandrew.com/weblog/articles/cbs-events
     * @author John Resig - http://ejohn.org/projects/flexible-javascript-events/
     * @param {Object} obj - a javascript object.
     * @param {String} evType - an event to attach to the object.
     * @param {Function} fn - the function that is attached to the event.
     */
    me.addEvent = function(obj, evType, fn){
    
        if (obj.addEventListener) {
            obj.addEventListener(evType, fn, false);
        } else if (obj.attachEvent) {
            obj['e' + evType + fn] = fn;
            obj[evType + fn] = function(){
                obj["e" + evType + fn](self.event);
            }
            obj.attachEvent("on" + evType, obj[evType + fn]);
        }
    }
    
    
    /**
     * Removes an event that is attached to a javascript object.
     *
     * @author Scott Andrew - http://www.scottandrew.com/weblog/articles/cbs-events
     * @author John Resig - http://ejohn.org/projects/flexible-javascript-events/	 * @param {Object} obj - a javascript object.
     * @param {String} evType - an event attached to the object.
     * @param {Function} fn - the function that is called when the event fires.
     */
    me.removeEvent = function(obj, evType, fn){
    
        if (obj.removeEventListener) {
            obj.removeEventListener(evType, fn, false);
        } else if (obj.detachEvent) {
            try {
                obj.detachEvent("on" + evType, obj[evType + fn]);
                obj[evType + fn] = null;
                obj["e" + evType + fn] = null;
            } 
            catch (ex) {
                // do nothing;
            }
        }
    }
    
    function removeEventAttribute(obj, beginName){
        var attributes = obj.attributes;
        for (var i = 0; i < attributes.length; i++) {
            var attribute = attributes[i]
            var name = attribute.name
            if (name.indexOf(beginName) == 0) {
                //obj.removeAttributeNode(attribute);
                attribute.specified = false;
            }
        }
    }
    
    me.addScrollWheelEvent = function(obj, fn){
        if (obj.addEventListener) {
            /** DOMMouseScroll is for mozilla. */
            obj.addEventListener('DOMMouseScroll', fn, true);
        }
        
        /** IE/Opera. */
        if (obj.attachEvent) {
            obj.attachEvent("onmousewheel", fn);
        }
        
    }
    
    me.removeScrollWheelEvent = function(obj, fn){
        if (obj.removeEventListener) {
            /** DOMMouseScroll is for mozilla. */
            obj.removeEventListener('DOMMouseScroll', fn, true);
        }
        
        /** IE/Opera. */
        if (obj.detachEvent) {
            obj.detatchEvent("onmousewheel", fn);
        }
        
    }
    
    /**
     * Given a mouse event, get the mouse pointer's x-coordinate.
     *
     * @param {Object} e - a DOM Event object.
     * @return {int} - the mouse pointer's x-coordinate.
     */
    me.getMouseX = function(e){
        if (!e) {
            return;
        }
        // NS4
        if (e.pageX != null) {
            return e.pageX;
            // IE
        } else if (window.event != null && window.event.clientX != null &&
        document.body != null &&
        document.body.scrollLeft != null) 
            return window.event.clientX + document.body.scrollLeft;
        // W3C
        else if (e.clientX != null) 
            return e.clientX;
        else 
            return null;
    }
    
    /**
     * Given a mouse event, get the mouse pointer's y-coordinate.
     * @param {Object} e - a DOM Event Object.
     * @return {int} - the mouse pointer's y-coordinate.
     */
    me.getMouseY = function(e){
        // NS4
        if (e.pageY != null) 
            return e.pageY;
        // IE
        else if (window.event != null && window.event.clientY != null &&
        document.body != null &&
        document.body.scrollTop != null) 
            return window.event.clientY + document.body.scrollTop;
        // W3C
        else if (e.clientY != null) {
            return e.clientY;
        }
    }
    /**
     * Given a mouse scroll wheel event, get the "delta" of how fast it moved.
     * @param {Object} e - a DOM Event Object.
     * @return {int} - the mouse wheel's delta.  It is greater than 0, the
     * scroll wheel was spun upwards; if less than 0, downwards.
     */
    me.getScrollWheelDelta = function(e){
        var delta = 0;
        if (!e) /* For IE. */ 
            e = window.event;
        if (e.wheelDelta) { /* IE/Opera. */
            delta = e.wheelDelta / 120;
            /** In Opera 9, delta differs in sign as compared to IE.
             */
            if (window.opera) {
                delta = -delta;
            }
        } else if (e.detail) { /** Mozilla case. */
            /** In Mozilla, sign of delta is different than in IE.
             * Also, delta is multiple of 3.
             */
            delta = -e.detail / 3;
        }
        return delta
    }
    
    /**
     * Sets a mouse move event of a document.
     *
     * @deprecated - use only if compatibility with IE4 and NS4 is necessary.  Otherwise, just
     * 		use EventHelpers.addEvent(window, 'mousemove', func) instead. Cannot be used to add
     * 		multiple mouse move event handlers.
     *
     * @param {Function} func - the function that you want a mouse event to fire.
     */
    me.addMouseEvent = function(func){
    
        if (document.captureEvents) {
            document.captureEvents(Event.MOUSEMOVE);
        }
        
        document.onmousemove = func;
        window.onmousemove = func;
        window.onmouseover = func;
        
    }
    
    
    
    /** 
     * Find the HTML object that fired an Event.
     *
     * @param {Object} e - an HTML object
     * @return {Object} - the HTML object that fired the event.
     */
    me.getEventTarget = function(e){
        // first, IE method for mouse events(also supported by Safari and Opera)
        if (e.toElement) {
            return e.toElement;
            // W3C
        } else if (e.currentTarget) {
            return e.currentTarget;
            
            // MS way
        } else if (e.srcElement) {
            return e.srcElement;
        } else {
            return null;
        }
    }
    
    
    
    
    /**
     * Given an event fired by the keyboard, find the key associated with that event.
     *
     * @param {Object} e - an event object.
     * @return {String} - the ASCII character code representing the key associated with the event.
     */
    me.getKey = function(e){
        if (e.keyCode) {
            return e.keyCode;
        } else if (e.event && e.event.keyCode) {
            return window.event.keyCode;
        } else if (e.which) {
            return e.which;
        }
    }
    
    
    /** 
     *  Will execute a function when the page's DOM has fully loaded (and before all attached images, iframes,
     *  etc., are).
     *
     *  Usage:
     *
     *  EventHelpers.addPageLoadEvent('init');
     *
     *  where the function init() has this code at the beginning:
     *
     *  function init() {
     *
     *  if (EventHelpers.hasPageLoadHappened(arguments)) return;
     *
     *	// rest of code
     *   ....
     *  }
     *
     * @author This code is based off of code from http://dean.edwards.name/weblog/2005/09/busted/ by Dean
     * Edwards, with a modification by me.
     *
     * @param {String} funcName - a string containing the function to be called.
     */
    me.addPageLoadEvent = function(funcName){
    
        var func = eval(funcName);
        
        // for Internet Explorer (using conditional comments)
        /*@cc_on @*/
        /*@if (@_win32)
         pageLoadEventArray.push(func);
         return;
         /*@end @*/
        if (isSafari) { // sniff
            pageLoadEventArray.push(func);
            
            if (!safariTimer) {
            
                safariTimer = setInterval(function(){
                    if (/loaded|complete/.test(document.readyState)) {
                        clearInterval(safariTimer);
                        
                        /*
                         * call the onload handler
                         * func();
                         */
                        me.runPageLoadEvents();
                        return;
                    }
                    set = true;
                }, 10);
            }
            /* for Mozilla */
        } else if (document.addEventListener) {
            var x = document.addEventListener("DOMContentLoaded", func, null);
            
            /* Others */
        } else {
            me.addEvent(window, 'load', func);
        }
    }
    
    var pageLoadEventArray = new Array();
    
    me.runPageLoadEvents = function(e){
        if (isSafari || e.srcElement.readyState == "complete") {
        
            for (var i = 0; i < pageLoadEventArray.length; i++) {
                pageLoadEventArray[i]();
            }
        }
    }
    /**
     * Determines if either addPageLoadEvent('funcName') or addEvent(window, 'load', funcName)
     * has been executed.
     *
     * @see addPageLoadEvent
     * @param {Function} funcArgs - the arguments of the containing. function
     */
    me.hasPageLoadHappened = function(funcArgs){
        // If the function already been called, return true;
        if (funcArgs.callee.done) 
            return true;
        
        // flag this function so we don't do the same thing twice
        funcArgs.callee.done = true;
    }
    
    
    
    /**
     * Used in an event method/function to indicate that the default behaviour of the event
     * should *not* happen.
     *
     * @param {Object} e - an event object.
     * @return {Boolean} - always false
     */
    me.preventDefault = function(e){
    
        if (e.preventDefault) {
            e.preventDefault();
        }
        
        try {
            e.returnValue = false;
        } 
        catch (ex) {
            // do nothing
        }
        
    }
    
    me.cancelBubble = function(e){
        if (e.stopPropagation) {
            e.stopPropagation();
        }
        
        try {
            e.cancelBubble = true;
        } 
        catch (ex) {
            // do nothing
        }
    }
	
	/* 
	 * Fires an event manually.
	 * @author Scott Andrew - http://www.scottandrew.com/weblog/articles/cbs-events
	 * @author John Resig - http://ejohn.org/projects/flexible-javascript-events/	 * @param {Object} obj - a javascript object.
	 * @param {String} evType - an event attached to the object.
	 * @param {Function} fn - the function that is called when the event fires.
	 * 
	 */
	me.fireEvent = function (element,event, options){
		
		if(!element) {
			return;
		}
		
	    if (document.createEventObject){
	        /* 
			var stack = DebugHelpers.getStackTrace();
			var s = stack.toString();
			jslog.debug(s);
			if (s.indexOf('fireEvent') >= 0) {
				return;
			}
			*/
			return element.fireEvent('on' + event, globalEvent)
			jslog.debug('ss');
			
	    }
	    else{
	        // dispatch for firefox + others
	        globalEvent.initEvent(event, true, true); // event type,bubbling,cancelable
	        return !element.dispatchEvent(globalEvent);
	    }
}
    
    /* EventHelpers.init () */
    function init(){
        // Conditional comment alert: Do not remove comments.  Leave intact.
        // The detection if the page is secure or not is important. If 
        // this logic is removed, Internet Explorer will give security
        // alerts.
        /*@cc_on @*/
        /*@if (@_win32)
        
         document.write('<script id="__ie_onload" defer src="' +
        
         ((location.protocol == 'https:') ? '//0' : 'javascript:void(0)') + '"><\/script>');
        
         var script = document.getElementById("__ie_onload");
        
         me.addEvent(script, 'readystatechange', me.runPageLoadEvents);
        
         /*@end @*/
        
    }
    
    init();
}

EventHelpers.addPageLoadEvent('EventHelpers.init');