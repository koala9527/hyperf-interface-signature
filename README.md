# hyperf-interface-signature
hyperf框架 在中间件中 使用签名混淆验证。读取请求header头sign，time-stamp，nonce-str和所有参数进行ascii排序拼接字符串，base64,md5 混淆后与签名验签。

根据当前时间戳和所有的请求参数来进行计算签名，所以每一次请求的header头内容都会不同

主要解决通过中间人接口拦截分析进行重放攻击的威胁，不会扣JS代码的不法分子就可以拒之门外啦，还可以加上一下AES对称加解密或者RSA非对称加解密进行自定义混淆操作，进一步提高安全性.



主要代码在app/Middlerware/AuthMiddleware.php中

# Postman Pre-request Script代码 

根据下面的代码可以做前端的接口签名混淆操作，跟Javascript语法非常相似

```javascript
request_time_stamp =  Math.round(new Date() / 1000);  // 获取秒级时间戳
token = pm.environment.get("sign-token")  //  读取环境变量，这里的环境变量应该在登录接口的Tests里面设置
pm.environment.set('sign-time-stamp',request_time_stamp)   //  读取设置环境变量，共所有接口使用
nonce_str = randomString(32)  
pm.environment.set('sign-nonce-str',nonce_str)  // 设置随机字符串环境变量 
var params_args = pm.request.url.query.members;  // 获取当前请求所有Get参数及其值
var body_args = request.data; // 获取当前请求所有Post参数及其值
for(var i=0;i<params_args.length;i++){
    body_args[params_args[i].key] = params_args[i].value;  // 合并Get参数Post参数的键和值
}
body_args['time_stamp'] = request_time_stamp;
body_args['nonce_str'] = nonce_str
body_args['token'] = token
body_args = objectsort(body_args)  //所有参数合并排序
console.log(body_args);
body_args_base64 = CryptoJS.enc.Base64.stringify(CryptoJS.enc.Utf8.parse(body_args)).toUpperCase()  //  base64混淆字母转大写
// console.log(body_args_base64);
sign = CryptoJS.MD5(body_args_base64).toString()  // MD5混淆
// console.log(sign);
sign_type = (request_time_stamp % 5) % 2;  //当前时间进行取余操作，判断奇偶数

// console.log(sign_type);

if(sign_type==1){  //根据时间戳求余奇偶数来进行混淆拼接，得出最后的sign
    new_sign =  sign + token
}else{
    new_sign =  token + sign
}
console.log(new_sign);
pm.environment.set('sign-sign',new_sign)   //设置sign参数，供全局接口使用
function objectsort(obj){
    let arr = new Array();
    let num = 0;
    for (let i in obj) {
        arr[num] = i;
        num++;
    }
    const sortArr = arr.sort();
    //自定义排序字符串
    let str = "";
    for (let i in sortArr) {
        str += sortArr[i] + "=" + obj[sortArr[i]] + "&";
    }
    //去除两侧&符号
    const char = "&";
    str = str.replace(new RegExp("^\\" + char + "+|\\" + char + "+$", "g"), "");
    return str;
}

/* 生成随即字符串 */
function randomString(len) {
  len = len || 32;
  const $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz-';
  const maxPos = $chars.length;
  let res = '';
  for (let i = 0; i < len; i++) {
    res += $chars.charAt(Math.floor(Math.random() * maxPos));
  }
  return res;
}


```
![image](https://user-images.githubusercontent.com/36888009/177742593-d82b2b7b-0463-476b-a760-a226b87a6905.png)

