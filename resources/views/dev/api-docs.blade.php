<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>TAD API v1 Documentation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:300,400,700|Roboto:300,400,700" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #ffffff;
        }
    </style>
</head>
<body>
    <redoc spec-url="{{ route('dev.openapi-yaml') }}"></redoc>
    <script src="https://cdn.redoc.ly/redoc/latest/bundles/redoc.standalone.js"></script>
</body>
</html>
