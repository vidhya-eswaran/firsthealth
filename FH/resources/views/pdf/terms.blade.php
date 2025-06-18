<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Terms and Conditions</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            font-size: 12px;
        }
        h1 {
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Terms and Conditions</h1>

    @foreach($terms as $term)
        {!! $term->terms !!}
    @endforeach

    <hr>
</body>
</html>
