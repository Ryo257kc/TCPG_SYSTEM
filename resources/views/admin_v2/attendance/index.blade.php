<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM &#21220;&#24608;&#31649;&#29702;</title>
    @include('admin_v2.attendance.partials.page_style')
</head>
<body>
<div class="wrap">
    @include('admin_v2.attendance.partials.header')

    <section class="panel" id="attendance-main">
        @include('admin_v2.attendance.partials.filters')
        @include('admin_v2.attendance.partials.shift_create_inline')
        @include('admin_v2.attendance.partials.table')
        @include('admin_v2.attendance.partials.daily_table')
    </section>
</div>
@include('admin_v2.attendance.partials.page_script')
</body>
</html>