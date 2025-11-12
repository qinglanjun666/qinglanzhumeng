@echo off
:: ==============================================
:: 绘斓网站 — 一键上传GitHub备份脚本（Windows版）
:: 作者：Jason
:: 日期：2025-11
:: ==============================================

echo.
echo ==============================================
echo   ?? 正在执行 Git 一键上传到 GitHub ...
echo ==============================================
echo.

:: 切换到当前脚本所在目录
cd /d %~dp0

:: 显示当前路径
echo 当前目录：%cd%
echo.

:: 添加所有修改的文件
git add .
if %errorlevel% neq 0 (
    echo ? git add 出错，请检查项目路径。
    pause
    exit /b
)

:: 提交更新（附带当前日期时间）
set now=%date% %time%
git commit -m "自动备份：%now%"
if %errorlevel% neq 0 (
    echo ?? 没有新变化或提交失败。
) else (
    echo ? 已提交修改。
)

:: 推送到远程分支（默认 main）
git push origin master
if %errorlevel% neq 0 (
    echo ? 推送失败，请检查网络或远程仓库配置。
    pause
    exit /b
)

echo.
echo ==============================================
echo ? 网站已成功备份到 GitHub！
echo ==============================================
echo.
pause
