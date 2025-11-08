# 本地字体文件

将以下字体文件（WOFF2格式）放入本目录，以启用本地托管字体并提升加载性能：

- Inter-Regular.woff2
- Inter-SemiBold.woff2
- Inter-Bold.woff2
- NotoSansSC-Regular.woff2
- NotoSansSC-Medium.woff2
- NotoSansSC-Bold.woff2

注意事项：
- 确保文件名与样式中的引用一致（styles.scss / styles.css）。
- Web 服务器需有读取权限；建议开启 gzip/brotli 压缩以优化传输。
- 如暂未放置字体文件，页面会优先使用系统字体，或通过回退的 Google Fonts 加载远程字体。