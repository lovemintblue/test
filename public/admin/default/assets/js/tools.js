/* 火狐下取本地全路径 */
function getFullPath(obj) {
    if (obj) {
        //ie
        if (window.navigator.userAgent.indexOf("MSIE") >= 1) {
            obj.select();
            if (window.navigator.userAgent.indexOf("MSIE") == 25) {
                obj.blur();
            }
            return document.selection.createRange().text;
        }
        //firefox
        else if (window.navigator.userAgent.indexOf("Firefox") >= 1) {
            if (obj.files) {
                //return obj.files.item(0).getAsDataURL();
                return window.URL.createObjectURL(obj.files.item(0));
            }
            return obj.value;
        }
        return obj.value;
    }
}

function showErrorMsg(msg)
{
    if(typeof(parent.layer)!="undefined"){
        parent.layer.msg(msg,{icon: 2,anim: 6});
    }else{
        layer.msg(msg,{icon: 2,anim: 6});
    }
}

function showSuccessMsg(msg,url)
{
    if(typeof(parent.layer)!="undefined"){
        parent.layer.msg(msg,{icon: 1});
    }else{
        layer.msg(msg,{icon: 1});
    }
    if(url){
        window.setTimeout(function(){
            window.location.href =url
        },2000);
    }
}


/**删除提示 */
function showConfirmMsg(msg,successCallback,errorCallback)
{
    if(typeof(parent.layer)!="undefined"){
        parent.layer.confirm(msg, {
            title:"系统提示",
            skin: 'layui-layer-admin',
            shade: .1,
            btn: ['确定','取消'] //按钮
        }, function(){
            parent.layer.closeAll();
            successCallback();
        }, function(){
            parent.layer.closeAll();
            if(typeof(errorCallback)!="undefined"){
                errorCallback();
            }
        });
    }else{
        layer.closeAll();
        layer.confirm(msg, {
            title:"系统提示",
            title:"系统提示",
            skin: 'layui-layer-admin',
            shade: .1,
            btn: ['确定','取消'] //按钮
        }, function(){
            successCallback();
        }, function(){
            if(typeof(errorCallback)!="undefined"){
                errorCallback();
            }
        });
    }
}


/**执行ajax请求 */
function doAjax(url, data, successCallback, errorCallback) {
    var $ = layui.jquery;
    $.ajax({
        url: url,
        data: data,
        type: "post",
        dataType: "json",
        success: function (res) {
            if (typeof (successCallback) != "undefined") {
                successCallback(res)
            }
        }, error: function (error) {
            if (typeof (errorCallback) != 'undefined') {
                errorCallback()
            }
        }
    })
}

function doUploadFile(url,file,successCallback, errorCallback) {
    var formData = new FormData();
    formData.append('file', file);
    $.ajax({
        url:url,
        type:"post",
        data: formData,
        contentType: false,
        processData: false,
        success: function(res) {
            if(res.status=='y'){
                if (typeof (successCallback) != 'undefined') {
                    successCallback(res)
                }
            }else{
                if (typeof (errorCallback) != 'undefined') {
                    errorCallback(res)
                }
            }
        },
        error:function(data) {
            if (typeof (errorCallback) != 'undefined') {
                errorCallback(data)
            }
        }
    });
}

/**执行ajax请求 */
function doLoadAjax(url, data, successCallback, errorCallback) {
    var loadIndex = layer.load(2);
    var $ = layui.jquery;
    $.ajax({
        url: url,
        data: data,
        type: "post",
        dataType: "json",
        success: function (res) {
            layer.close(loadIndex);
            if (typeof (successCallback) != "undefined") {
                successCallback(res)
            }
        }, error: function (error) {
            layer.close(loadIndex);
            if (typeof (errorCallback) != 'undefined') {
                errorCallback()
            }
        }
    })
}

/**
 * 表單數據驗證
 * @param validateForm 要驗證的表單
 */
function Validform(validateForm, btn, callback) {
    var config = {
        ignoreHidden: true,
        datatype: { //传入自定义datatype类型，可以是正则，也可以是函数（函数内会传入一个参数）;
            "f": /^(-?\d+)(\.\d+)?$/  //浮点数
        },
        tiptype: function (msg, o, cssctl) {
            var status = o.type;//验证状态 ---2：通过验证，3：验证失败
            var dom = o.obj;

            if (status == 3) {
                $(dom).parent().parent().addClass("bad");
                $em = $(dom).parent().parent().find('.alert');
                if ($em.length < 1) {
                    $(dom).parent().parent().append('<div class="alert"><label  class="error"><i class="fa fa-exclamation-circle"></i>' + msg + '</label></div>');
                } else {
                    $em.html('<label  class="error"><i class="fa fa-exclamation-circle"></i>' + msg + '</label>');
                }
                $(dom).addClass('error');
                $em.show();

            } else {
                $(dom).parent().parent().removeClass('bad');
                $em = $(dom).parent().parent().find('.alert');
                $(dom).removeClass('error');
                $em.hide();
                $em.html('');
            }
        },
        beforeSubmit: function (curform) {
            if (typeof (callback) != 'undefined') {
                return callback(curform);
            }
            return true;
        }
    };
    if (typeof (btn) != "undefined" && btn !== null) {
        config.btnSubmit = btn;
    }
    $(validateForm).Validform(config);
}

function trim(str) {
    return (str + '').replace(/(\s+)$/g, '').replace(/^\s+/g, '');
}


function addCookie(name, value, expireHours) {
    var cookieString = name + "=" + escape(value) + "; path=/";
    //判断是否设置过期时间
    if (expireHours > 0) {
        var date = new Date();
        date.setTime(date.getTime() + expireHours * 3600 * 1000);
        cookieString = cookieString + ";expires=" + date.toGMTString()+';path=/';
    }
    document.cookie = cookieString;
}

function setCookie(name, value, days) {
    var exp = new Date();
    exp.setTime(exp.getTime() + days * 24 * 60 * 60 * 1000);
    var arr = document.cookie.match(new RegExp("(^| )" + name + "=([^;]*)(;|$)"));
    document.cookie = name + "=" + escape(value) + ";expires=" + exp.toGMTString()+';path=/';
}

function getCookie(name) {
    var arr = document.cookie.match(new RegExp("(^| )" + name + "=([^;]*)(;|$)"));
    if (arr != null) {
        return unescape(arr[2]);
    }
    return null;
}

function delCookie(name) {
    var exp = new Date();
    exp.setTime(exp.getTime() - 10);
    var cval = getCookie(name);
    if (cval != null) {
        document.cookie = name + "=" + cval + ";expires=" + exp.toGMTString()+';path=/';
    }
}


function isDisableDom(obj) {
    var disable = $(obj).attr('data-disable');
    if (typeof (disable) != "undefined" && disable == 'y') {
        return true;
    }
    return false;
}

function doDisableDom(obj, type) {

    var disable = 'n';
    if (type) {
        disable = 'y';
    }
    $(obj).attr('data-disable', disable);
}


function checkFloatNum(obj) {

    var keep = $(obj).attr("data-keep-num");
    if (typeof (keep) == "undefined" || keep == "" || keep == null) {
        keep = 2;
    } else {
        keep = parseInt(keep);
    }

    var per = "";
    for (var i = 0; i < keep; i++) {
        per += "\\d";
    }
    var per = '/^(\\-)*(\\d+)\\.(' + per + ').*$/';
    per = eval(per);
    obj.value = obj.value.replace(/[^\d.]/g, "");  //清除“数字”和“.”以外的字符
    obj.value = obj.value.replace(/\.{2,}/g, "."); //只保留第一个. 清除多余的
    obj.value = obj.value.replace(".", "$#$").replace(/\./g, "").replace("$#$", ".");
    obj.value = obj.value.replace(per, '$1$2.$3');//只能输入两个小数
    if (obj.value.indexOf(".") < 0 && obj.value != "") {//以上已经过滤，此处控制的是如果没有小数点，首位不能为类似于 01、02的金额
        obj.value = parseFloat(obj.value);
    }
    return obj.value;
}