# SteamSignature

一个简单的steam信息签名档

## 使用方法

http://www.examples.com/signature.php?steamid=xxxxxxxxxxxxxxxxx

或者使用重定向，类似于这样

    rewrite ^/(\d+)\.png$ /signature.php?steamid=$1;

当然也可以直接用我的

https://lzzr.me/steam/signature/xxxxxxxxxxxxxxxxx.png

不过我不保证这个随时可用

## 许可协议

项目采用[Apache-2.0](https://opensource.org/licenses/Apache-2.0)许可

其中字体使用[Noto Sans CJK](https://github.com/googlei18n/noto-cjk/)遵守[OFL-1.1](https://opensource.org/licenses/OFL-1.1)