Add-Type -AssemblyName System.Drawing

$srcPath = "c:\xampp\htdocs\DemoWebsite\assets\images\reikilogo.jpg"
$outPath = "c:\xampp\htdocs\DemoWebsite\assets\images\favicon-circle.png"

$img = [System.Drawing.Image]::FromFile($srcPath)
$size = [Math]::Min($img.Width, $img.Height)

$bmp = New-Object System.Drawing.Bitmap($size, $size)
$g = [System.Drawing.Graphics]::FromImage($bmp)
$g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
$g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
$g.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
$g.Clear([System.Drawing.Color]::Transparent)

$path = New-Object System.Drawing.Drawing2D.GraphicsPath
$path.AddEllipse(0, 0, $size, $size)
$g.SetClip($path)
$g.DrawImage($img, 0, 0, $size, $size)

$pen = New-Object System.Drawing.Pen([System.Drawing.ColorTranslator]::FromHtml('#C9A84C'), 8)
$g.ResetClip()
$g.DrawEllipse($pen, 4, 4, $size - 8, $size - 8)

$bmp.Save($outPath, [System.Drawing.Imaging.ImageFormat]::Png)

$g.Dispose()
$img.Dispose()
$bmp.Dispose()

Write-Host "Circular PNG favicon generated successfully!"
