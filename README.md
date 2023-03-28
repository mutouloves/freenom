## 说明
Freenom 域名自动续期

由于企业微信应需要可信 IP，对于动态 IP 不太友好，需要经常添加 IP，操作繁琐。为方便本人使用，故在原作者的基础上，添加了企业微信机器人的推送。

由于没有代码基础，本修改基于原作者的企业微信应用修改，在此特别感谢原作者的项目，更新一切随缘！

镜像基于 [ Freenom 域名自动续期 ](https://github.com/luolongfei/freenom) 实现。

[![Docker 拉取地址](https://img.shields.io/docker/pulls/mutouloves/freenom.svg)](https://hub.docker.com/r/mutouloves/freenom)
[![Docker 镜像大小](https://img.shields.io/docker/image-size/mutouloves/freenom.svg)](https://hub.docker.com/r/mutouloves/freenom)
[![Docker 镜像版本](https://img.shields.io/docker/v/mutouloves/freenom.svg)](https://hub.docker.com/r/mutouloves/freenom)
[![Sponsors  资助](https://img.shields.io/github/sponsors/mutouloves.svg)](https://raw.githubusercontent.com/mutouloves/i/f/f.svg)

---

## 安装
拉取 docker 镜像并从主机挂载卷以持久保存：

随机时间
```sh
docker pull mutouloves/freenom
sudo docker run -d \
--name=freenom \
--restart unless-stopped \
-v freenom/conf:/conf \
-v freenom/logs:/app/logs \
-e TZ=Asia/Shanghai \
mutouloves/freenom
```
指定时间
```sh
docker pull mutouloves/freenom
sudo docker run -d \
--name=freenom \
--restart unless-stopped \
-v freenom/conf:/conf \
-v freenom/logs:/app/logs \
-e TZ=Asia/Shanghai \
-e RUN_AT="11:24" \
mutouloves/freenom
```

上面这条命令只比上上条命令多了个 -e RUN_AT="11:24"，其中11:24表示在北京时间每天的 11:24 执行续期任务，你可以自定义这个时间。 这里的RUN_AT参数同时也支持 CRON 命令里的时间形式，比如， -e RUN_AT="9 11 * * *"，表示每天北京时间 11:09 执行续期任务， 如果你不想每天执行任务，只想隔几天执行，只用修改RUN_AT的值即可。

注意：不推荐自定义脚本执行时间。因为你可能跟很多人定义的是同一个时间点，这样可能导致所有人都是同一时间向 Freenom 的服务器发起请求， 使得 Freenom 无法稳定提供服务。而如果你不自定义时间，程序会自动指定北京时间 06 ~ 23 点全时段随机的一个时间点作为执行时间， 每次重启容器都会自动重新指定。

---

## 用法
如何验证你的配置是否正确呢？

修改并保存.env文件后，执行docker restart freenom重启容器，等待 5 秒钟左右，然后执行docker logs freenom查看输出内容， 观察输出内容中有执行成功 字样，则表示配置无误。如果你还来不及配置送信邮箱等内容，可先停用邮件功能。

## 鸣谢

[luolongfei/freenom](https://github.com/luolongfei/freenom) PHP自动续期脚本项目。

<!---
这里是注释
---

## 投食

<img src="https://raw.github.com/mutouloves/i/f/z.svg" width="600px">

-->
[comment]: <> (括号内是注释<img src="https://raw.github.com/mutouloves/i/f/z.svg" width="600px">)
[//]: <> (括号内是注释<img src="https://raw.github.com/mutouloves/i/f/z.svg" width="600px">)
[//]: # (括号内是注释<img src="https://raw.github.com/mutouloves/i/f/z.svg" width="600px">)
