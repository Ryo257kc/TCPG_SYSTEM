<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>カレンダーマスタ</title>
    @include('admin_v2.master.calendar.partials.page_style')
</head>
<body>
<div class="page">
<div class="card">
    @include('admin_v2.master.calendar.partials.header')
    @include('admin_v2.master.calendar.partials.controls')
    @include('admin_v2.master.calendar.partials.add_form')
    @include('admin_v2.master.calendar.partials.table')
</div>
</div>
@include('admin_v2.master.calendar.partials.page_script')
</body>
</html>