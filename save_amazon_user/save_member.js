// qq member表里面role字段为权限。0:群主，1：管理员，2：普通用户
 
//  !function(){
    
  
    
//     class Main{
//         constructor(){
           
//         }

//         get_list_data(){
//             // var url = "https://www.amazon.com/gp/";
//             var list = ["A290IJ0WM3AHYY","AWJZDZZIEDGRU"];
//             new Member(list);
//         }

//         run(){
//             chrome.tabs.executeScript( { file: 'jquery-3.0.0.min.js' } );
//             chrome.tabs.executeScript( { file: 'content_script.js' } );
//         }
//     }
    

//     var main = new Main();
//     window.run = main.start;
// }();


chrome.runtime.onMessage.addListener(function(request, sender, callback) {
    $.ajax(request).done(function(data){
        callback(data);
    });
    return true;  // prevents the callback from being called too early on return
});