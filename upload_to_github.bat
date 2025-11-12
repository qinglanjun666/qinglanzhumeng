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

:: 添加所有修改
git add .

:: 生成带日期时间的提交信息
set now=%date% %time%
git commit -m "自动备份：%now%"

:: 检查当前分支
for /f "tokens=*" %%i in ('git rev-parse --abbrev-ref HEAD') do set BRANCH=%%i
echo 当前分支：%BRANCH%
echo.

:: 推送到远程仓库
echo 正在推送到 GitHub，请稍候...
git push origin %BRANCH%

:: 检查是否成功
if %errorlevel%==0 (
    echo.
    echo ? 上传成功！
    echo 正在打开 GitHub 最新提交页面...
    :: ?? 请在下面修改为你的 GitHub 仓库地址
    set REPO_URL=https://github.com/qinglanjun666/qinglanzhumeng
    start %REPO_URL%/commits/%BRANCH%
) else (
    echo.
    echo ? 上传失败，请检查网络或仓库权限。
)

echo.
pause
