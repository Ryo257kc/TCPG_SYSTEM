<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM 会社マスタ</title>
    @include('admin_v2.master.company.partials.page_style')
</head>
<body>
<div class="page">
    <div class="card">
        @include('admin_v2.master.company.partials.header')
        @include('admin_v2.master.company.partials.filter_form')
        @include('admin_v2.master.company.partials.content')
    </div>
</div>
@include('admin_v2.master.company.partials.page_script')
</body>
</html>
