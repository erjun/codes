// Copyright (c) 2014 The Chromium Authors. All rights reserved.
// Use of this source code is governed by a BSD-style license that can be
// found in the LICENSE file.

/**
 * Get the current URL.
 *
 * @param {function(string)} callback - called when the URL of the current tab
 *   is found.
 */
function getCurrentTabUrl(callback) {
  var queryInfo = {
    active: true,
    currentWindow: true
  };

  chrome.tabs.query(queryInfo, function(tabs) {
    var tab = tabs[0];
    var url = tab.url;
    console.assert(typeof url == 'string', 'tab.url should be a string');

    callback(url);
  });

}
$(function(){
   
    $("#start").on('click', function(){
        getCurrentTabUrl(function(url) {
          if(url.match("qun.qq.com/member")){
              var page = chrome.extension.getBackgroundPage();
             page.run(url);
          }else{
             var url = "http://qun.qq.com/member.html";
            //  log("跳转到"+url+"并登陆");
             chrome.tabs.create({url:url});
          }
      });
    });

    chrome.extension.onMessage.addListener(
      function(request, sender, sendResponse) {
        var text = request.log;
        $(".status").first().before("<div class='status'>"+text+"</div>");
        console.log(text);
    });
   
});

