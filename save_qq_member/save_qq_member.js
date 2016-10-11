// qq member表里面role字段为权限。0:群主，1：管理员，2：普通用户
 
 !function(){
     class User{
        constructor(){

        }
        check(){
           this.login();
           
        }

        login(){
            if(this.username && this.password){
               log("自动登陆中...");
           }else{
               log("请登录");
           }
           chrome.tabs.executeScript(null,{code:"alert('请登录qq群网页')"});
        }
     }
     var cookie_key;
     var popup;
     var debug;
     var join_time_sort;
     var last_speak_time_sort;
     var group_count;
     var group_complete;
     var host;
     var user = new User();
     
     function getCookie(callback){
         var details= {
             url:"http://qun.qq.com/",
             name:"skey"
         };
         chrome.cookies.get(details, function(cookie){
             if(cookie){
                 cookie_key = cookie.value;
             }else{
                 log("cookie key skey not found!");
             }
             callback();
         });
     }
     
    function t() {
            for (var e = cookie_key, t = 5381, n = 0, o = e.length; o > n; ++n) t += (t << 5) + e.charAt(n).charCodeAt();
            return 2147483647 & t
    }
   
    function log(m){
        var time = new Date().toLocaleString();
        var message = new Date();
        m = time +" "+ m;
        chrome.extension.sendMessage({log: m}, function(response) {
            // console.log(response);
        });

        console.log(m);
    }

    function all_completed(){
        setTimeout( function(){
            if(main){
                log("main run again!");
                main.run();
            }else{
                throw new error("main variable undefind!");
            }
        },1000 * 60 * 60);
        
    }

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
   
    class QQ_member{
        constructor(gid, last_join_time, last_speak_time,type) {
            // 分页
            this.hasNext = true; 
            this.defNum = 20;
            this.end = this.defNum;
            this.st = 0;

            // group id
            this.gid = gid;
            this.last_join_time = last_join_time;
            this.last_speak_time = last_speak_time;
            this.type = type;

            // 增加和更新了多少个
            this.add_number = 0;
            this.up_number = 0;

            // 已经完成多少
            this.complete_num = 0;
            this.member_count = 0;

            this.next_page();
        }
        timeout_ajax(ajax_fun){
            var that = this;
            setTimeout(function() {
                ajax_fun.call(that);
            }, 1000 * 10);
        }
        get_data(){
           
            var sort = join_time_sort;
            if(this.type == "up"){
                sort = last_speak_time_sort;
            }
            var gid = this.gid;
            var url = "http://qun.qq.com/cgi-bin/qun_mgr/search_group_members";
            var that = this;
            var post_data = {
                type: "POST",
                url: url,
                dataType: "json",
                data: {
                    gc: gid,
                    st: this.st,
                    end: this.end,
                    sort: sort,
                    bkn: t()
                },
                success: function(data){
                    var count = that.member_count = data.search_count || data.count;
                    that.hasNext = that.end >= count ? false : true;
                    count > that.st && (that.st = that.end + 1, that.end = that.st + that.defNum, that.end >= count && (that.end = count));
                    if(!data.mems) return;
                    var data_count = data.mems.length;
                    if(that.type == "add"){
                         if(data.mems[data_count - 1].join_time < that.last_join_time){
                             that.hasNext = false;
                         }
                    }else if(that.type == "up"){
                         if(data.mems[data_count - 1].last_speak_time < that.last_speak_time){
                             that.hasNext = false;
                         }
                    }
                    var data = that.add_gid(data.mems, gid);

                    that.send_data(data);
                }
            };
            $.ajax(post_data);
        }

        next_page(){
            if(this.hasNext){
                this.timeout_ajax(this.get_data);
                // this.get_data();
            }else{
            }
        }

        send_data(data){
            var that = this;
            var post = {
                url : host + "/offer/get_qq_list",
                crossDomain: true,
                dataType:"json",
                data:{string_data: JSON.stringify(data)},
                success:function(response){
                if(response.status == "ok"){
                    that.complete_num++;
                    that.add_number += response.add_number;
                    that.up_number += response.up_number;
                    log(response.message + ' ok ' + that.complete_num);
                    that.next_page();
                    if(!that.hasNext){
                        log(that.gid + " group complete,count "+that.member_count+",add number " + that.add_number + ",up number " + that.up_number);
                        group_complete++;
                        if(group_complete >= group_count){
                            log("count " + group_count + " group complete");
                            all_completed();
                            // chrome.tabs.executeScript(null,{code:"alert('所有都已完成')"});
                        }
                    }
                }
                },
                error:function(e,x,thrownError){
                    log("send_data function ajax " + thrownError);
                }
            } 
            $.ajax(post);
        }

         add_gid(data, gid){
            for(var i=0; i < data.length; i++){
                data[i]["gid"] = gid;
                data[i]["lv_level"] = data[i].lv.level;
                data[i]["lv_point"] = data[i].lv.point;
                delete data[i].lv;
            }
            return data;
        }
    }
    
    class QQ_group{
        constructor() {
            this.get_group_list();
        }

        get_group_list(){
            
            var url = "http://cdn.yishu.net/assets/nextad/group_list.json?t=" + (new Date().getTime());
            var that = this;
            log("request " + url + "...");
            $.ajax({url:url,type: "GET",crossDomain: true,dataType: "json",success:function(d){
                log("request success " + url);
                // TODO 这是我自己的qq群
                // d = [109811304];
                group_count = d.length;
                that.get_group(d);
            },error:function(xhr, status, error){
                log("get_group_list function ajax " + error);
            }});
        }
        get_group(group_list){
            var url = "http://qun.qq.com/cgi-bin/qun_mgr/get_group_list";
            var that = this;
            var post_data = {
                type: "POST",
                url: url,
                dataType: "json",
                data: {
                    bkn: t()
                },
                success: function(data){
                    var join = data.join;
                    if(!join){
                        user.check();
                        // log("请登录" + data.em);
                        // log(data);
                    }else{
                         log("group count is "+group_list.length+" ");
                        for(var i=0; i<group_list.length; i++){
                            var group = that.get_group_item(join, group_list[i]);
                            if(group == null){
                                log("error group not found " + group_list[i]);
                                group_count--;
                            }else{
                                that.save_group(group);
                            }
                        }
                    }
                   
                }
            };
            $.ajax(post_data);
        }
        save_group(group){
            var that = this;
            var post = {
                url : host + "/offer/save_group",
                dataType:"json",
                data:{string_data: JSON.stringify(group)},
                success:function(response){
                    if(response.status == "ok"){
                        var gid = group.gc;
                        that.get_group_info(gid);
                    }
                },
                error:function(e,x,thrownError){
                    log("save_group function ajax " + thrownError);
                }
            } 
            $.ajax(post);
        }
        get_group_info(gid){
            log("get group info " + gid);
            var post = {
                url : host + "/offer/get_qq_info",
                dataType:"json",
                data:{gid: gid},
                success:function(data){
                    if(data.status == "ok"){
                        // 如果是0代表第一次添加大量数据，只执行一次
                        if(data.last_join_time == -1 && data.last_speak_time == -1){
                            log("add "+gid+" group member");
                            new QQ_member(data.gid, data.last_join_time, data.last_speak_time, "add");
                        }else{
                            group_count = group_count * 2;
                            log("add "+gid+" group member");
                            new QQ_member(data.gid, data.last_join_time, data.last_speak_time, "add");
                            log("up "+gid+" group member");
                            new QQ_member(data.gid, data.last_join_time, data.last_speak_time, "up");
                        }
                       
                    }
                },
                error:function(e,x,thrownError){
                    log("get_group_info function ajax error " + thrownError);
                }
            } 
            $.ajax(post);
        }
        get_group_item(group_list, find_gc){
            for(var i=0; i<group_list.length; i++){
                if(group_list[i].gc == find_gc){
                    return group_list[i];
                }
            }
            return null;
        }
    }

   

    class Main{
        constructor(){
            // this.timer = null;
            // this.start();
        }

        start(){
            main.run();
        }
        stop(){
            // clearInterval(this.timer);
        }

        init(){
            log("init");
            cookie_key = null;
            popup = null;
            debug = true;
            join_time_sort = 10;
            last_speak_time_sort = 16;
            group_count = 0;
            group_complete = 0;
            host = "";
                
            if(debug){
               host = "http://dev.cocacoins.com";
            }
        }

        run(){
            this.init();
            getCookie(function(){
                if(cookie_key){
                    log("开始");
                    new QQ_group();
                }else{
                    user.check();
                }
            });
        }
    }
    var main = new Main();
    window.run = main.start;
}();
