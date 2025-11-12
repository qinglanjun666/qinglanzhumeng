@echo off
chcp 65001 >nul
title Git 一键上传到 GitHub
color 0A

echo ==============================================
echo    ?? 正在执行 Git 一键上传到 GitHub ...
echo ==============================================
echo.

:: 进入项目目录（修改为你自己的网站路径）
cd /d "C:\xampp\htdocs\huilanweb"

:: 显示当前目录
echo 当前目录：%cd%
echo.

:: 添加所有文件
git add .

:: 生成带日期时间的提交信息
set now=%date% %time%
git commit -m "自动备份：%now%"

:: 推送到 GitHub
echo.
echo 正在推送到 GitHub 远程仓库，请稍候...
git push

:: 检查是否成功
if %errorlevel%==0 (
    echo.
    echo ? 上传成功！
    echo.
    echo 正在打开 GitHub 页面...
    :: ?? 请在下面修改为你的 GitHub 仓库网址
    start https://github.com/qinglanjun666/qinglanzhumeng.git
) else (
    echo.
    echo ? 上传失败，请检查网络或仓库权限。
)

echo.
pause
