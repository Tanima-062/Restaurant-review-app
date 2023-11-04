<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>up</title>
</head>
<body>
<form action="upload_test" method="post" enctype="multipart/form-data">
    @csrf
    <input type="file" name="fname">
    <input type="submit" value="アップロード">
</form>
</body>
</html>
