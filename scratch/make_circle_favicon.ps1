Add-Type -AssemblyName System.Drawing
$srcPath = "c:\xampp\htdocs\DemoWebsite\assets\images\reikilogo1.png"
$outPath = "c:\xampp\htdocs\DemoWebsite\assets\images\favicon-circle.png"

$img = [System.Drawing.Image]::FromFile($srcPath)
$size = 256
$bmp = New-Object System.Drawing.Bitmap($size, $size)
$g = [System.Drawing.Graphics]::FromImage($bmp)
$g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
$g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
$g.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
$g.Clear([System.Drawing.Color]::Transparent)

# Draw white circle fill
$brush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::White)
$g.FillEllipse($brush, 6, 6, $size - 12, $size - 12)

# Draw logo with neat margin inside the circle
$padding = 24
$innerSize = $size - ($padding * 2)
$g.DrawImage($img, $padding, $padding, $innerSize, $innerSize)

# Draw gold border
$pen = New-Object System.Drawing.Pen([System.Drawing.ColorTranslator]::FromHtml('#C9A84C'), 8)
$g.DrawEllipse($pen, 6, 6, $size - 12, $size - 12)

$bmp.Save($outPath, [System.Drawing.Imaging.ImageFormat]::Png)
$g.Dispose()
$brush.Dispose()
$pen.Dispose()
$img.Dispose()
$bmp.Dispose()

Write-Host "Updated favicon-circle.png with reikilogo1.png"
