 !function(){

    class Member{
        constructor(list) {
            this.running = true;
            this.id_list = list;
            this.savedFileEntry = null;
            // 分页
            this.hasNext = true; 
            this.next_page();
            this.data_list = [];
            
        }
        timeout_ajax(ajax_fun){
            var that = this;
            setTimeout(function() {
                ajax_fun.call(that);
            }, 1000 * 1);
        }
        get_data(){
            var id = this.id_list.pop();
            if(id == undefined || !this.running) {
                return;
            }
            var url = "https://www.amazon.com/gp/profile/"+id+"/customer_email";
            var that = this;
            this.check_item(url, function(data){
                if(data.exist){
                    console.log(url + "已经存在");
                    that.get_data();
                    return;
                }
                
                var post_data = {
                    type: "GET",
                    url: url,
                    success: function(data){
                        var url = this.url;
                        url = url.replace("https://www.amazon.com/gp/profile/", "");
                        var key = url.replace("/customer_email", "");
                        data.data.profile_id = key;
                        data.data.profile_url = this.url;
                        that.save_data(data.data);
                    },error:function(xhr){
                        if(xhr.status == 404){
                            var url = this.url;
                            url = url.replace("https://www.amazon.com/gp/profile/", "");
                            var key = url.replace("/customer_email", "");
                            that.save_data({"profile_id":key,"email":"","profile_url":this.url});
                        }else{
                            that.next_page();
                        }
                    }
                };
                jQuery.ajax(post_data);
            });
            
        }
        check_item(key,callback){
            var url = "http://manage.ysk.cn/amazon/check_item_exist";
            var post_data = {
                type: "GET",
                url: url,
                data:{"key":key}
            };
             chrome.runtime.sendMessage(post_data, function(mes){
                 callback(mes);
            });
        }

        next_page(){
            if(this.hasNext && this.running){
                this.timeout_ajax(this.get_data);
            }
        }

        save_data(data){
            // this.data_list.push(data);
            // console.log(data);
            this.send_data(data);
            this.next_page();
        }
        send_data(data){
            var url = "http://manage.ysk.cn/amazon/save_item";
            var post_data = {
                type: "GET",
                url: url,
                data:data
            };
            // jQuery.ajax(post_data);
            chrome.runtime.sendMessage(post_data, function(mes){
                console.log(mes);
            });
        }

    }
    var member = null;
    class Main{
        constructor(){
            this.member = null;
        }
        open_file(callback){
            var fileChooser = document.createElement("input");
            fileChooser.type = 'file';

            fileChooser.addEventListener('change', function (evt) {
            var f = evt.target.files[0];
            if(f) {
                var reader = new FileReader();
                reader.onload = function(e) {
                var contents = e.target.result;
                   callback(contents);
                }
                reader.readAsText(f);
            }
            });

            document.body.appendChild(fileChooser);
            fileChooser.click();
        }
        stop(){
            // console.log(member);
            if(member){
                member.running = false;
            }
        }
        run(){
             if(member){
                console.log("正在运行");
                return;
            }
            this.open_file(function(data){
                var arr = JSON.parse(data);
                member = new Member(arr);
            });
           // test
           
        //    var arr = ["A290IJ0WM3AHYY"];
        //    var arr = ["A290IJ0WM3AHYY","AWJZDZZIEDGRU"];
        //    new Member(arr);
      
        }
    }
    var main = new Main();
    main.run();
    window.stop = main.stop;
    // run();
}();
