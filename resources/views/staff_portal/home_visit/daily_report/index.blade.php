<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 往診日報</title>
    <link rel="stylesheet" href="<?php echo e(asset('css/staff_portal/app-shell.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/staff_portal/data_table.css')); ?>">
</head>

<body>
    <main class="container">
        <?php echo $__env->make('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <style>
            .home-visit-row {
                background-color: #fff3cd;
            }
        </style>

        <section
            class="panel">
            <div class="content-head">
                <h2 class="content-title">往診日報</h2>
            </div>

            <?php if(session('status')): ?>
            <div class="status"><?php echo e(session('status')); ?></div>
            <?php endif; ?>

            <?php if($errors->any()): ?>
            <div class="error"><?php echo e($errors->first()); ?></div>
            <?php endif; ?>
            <div class="toolbar-group filter-row ">
                <form method="get" action="<?php echo e(route('home_visit.daily_report')); ?>">
                    <input type="date" name="date" value="<?php echo e($date); ?>">
                    <?php if($canSelectStaff ?? false): ?>
                    <select name="staff_name" class="margin_l20">
                        <?php $__currentLoopData = $staffOptions ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $staff): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($staff['staff_id']); ?>" <?php if(($targetStaffId ?? '') === $staff['staff_id']): echo 'selected'; endif; ?>>
                            <?php echo e($staff['staff_name']); ?>
                        </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php endif; ?>
                    <button class="margin_l20" type="submit">表示</button>
                    <?php if($items->where('is_confirmed', 1)->count() > 0): ?>
                    <input type="button" value="印刷" class="btn margin_l20" onclick="window.open('<?php echo e(route('home_visit.daily_report.print', ['date' => $date, 'staff_name' => $targetStaffId ?? ''])); ?>', '_blank')">
                    <?php endif; ?>
                    <input type="button" value="前週へ複製" class="btn margin_l20"
                        onclick="if(confirm('前週に複製します。よろしいですか？')) location.href='<?php echo e(route('home_visit.daily_report.copy_week', ['date' => $date, 'staff_name' => $targetStaffId ?? ''])); ?>'">
                </form>
                <?php if($items->where('is_confirmed', 1)->count() === 0): ?>
                <form method="post" action="<?php echo e(route('home_visit.daily_report.quick_store')); ?>" style="display:inline;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="date" value="<?php echo e($date); ?>">
                    <input type="hidden" name="staff_name" value="<?php echo e($targetStaffId ?? ''); ?>">
                    <button type="submit" class="btn margin_l20">新規追加</button>
                </form>

                <form method="post" action="<?php echo e(route('home_visit.monthly_report.confirm')); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="date" value="<?php echo e($date); ?>">
                    <input type="hidden" name="staff_name" value="<?php echo e($targetStaffId ?? ''); ?>">
                    <button class="btn apply margin_l20">確定</button>
                </form>
                <?php endif; ?>

            </div>
            <?php if(count($items) === 0): ?>
            <div class="empty">対象日のデータがありません。</div>
            <?php else: ?>

            <font size="small">（※黄色=往診）</font>
            <?php if($items->first() && $items->first()->is_confirmed == 1): ?>
            <font color="red">
                <?php echo e(\Carbon\Carbon::parse($items->first()->confirmed_at)->format('Y-m-d H:i:s')); ?>　確定済
            </font>
            <?php endif; ?>
            <!-- <div style="color:red; font-weight:bold;">
                件数：<?php echo e($items->count()); ?>

            </div> -->
            <div class="table-wrap">
                <table class="data-table">
                    <colgroup>
                        <col style="width: 40px;">
                        <col style="width: 140px;">
                        <col style="width: 50px;">
                        <col style="width: 50px;">
                        <col style="width: 50px;">
                        <col style="width: 70px;">
                        <col style="width: 50px;">
                        <col style="width: 60px;">
                        <col style="width: 60px;">
                        <col style="width: 60px;">
                        <col style="width: 40px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>患者名</th>
                            <th>距離</th>
                            <th>開始</th>
                            <th>終了</th>
                            <th>日報店舗</th>
                            <th>標準距離</th>
                            <th>売上金額</th>
                            <th>自費</th>
                            <th>未収金</th>
                            <th>詳細</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr class="<?php echo e($item->is_home_visit ? 'home-visit-row' : ''); ?>">
                            <td><?php echo e($loop->iteration); ?></td>
                            <td><?php echo e($item->patient_name); ?></td>
                            <td>
                                <?php echo e((is_numeric($item->distance) && $item->distance != 0)
                                ? (floor($item->distance) == $item->distance
                                    ? (int)$item->distance
                                    : rtrim(rtrim($item->distance, '0'), '.'))
                                : ''); ?>

                            </td>
                            <?php
                            $start = $item->start_time ? \Carbon\Carbon::parse($item->start_time)->format('H:i') : '';
                            $end = $item->end_time ? \Carbon\Carbon::parse($item->end_time)->format('H:i') : '';
                            ?>

                            <td><?php echo e($start !== '00:00' ? $start : ''); ?></td>
                            <td><?php echo e($end !== '00:00' ? $end : ''); ?></td>
                            <td><?php echo e($item->daily_report_store_name); ?></td>
                            <td><?php echo e(formatNumber($item->standard_distance)); ?></td>
                            <td><?php echo e(formatNumber(((float) ($item->sales_amount ?? 0)) * 100)); ?></td>
                            <td><?php echo e(formatNumber($item->private_fee)); ?></td>
                            <td><?php echo e(formatNumber($item->uncollected_amount)); ?></td>
                            <td>
                                <?php
                                $canShowDetail = ($isVisitManagement || $isAdmin)
                                ? empty($item->is_management_fixed)
                                : ($items->first() && $items->first()->is_confirmed != 1);
                                ?>
                                <?php if($canShowDetail && !empty($item->daily_report_id)): ?>
                                <a href="<?php echo e(route('home_visit.daily_report.edit', ['nippouNo' => $item->daily_report_id])); ?>" class="btn btn_small">詳細</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>

            </div>
            <?php endif; ?>
            <?php if($summary && ($summary->total_patients || $summary->duplicate_patients || $summary->total_distance)): ?>
            <p>
                人数：<?php echo e(($summary->total_patients ?? 0) - ($summary->duplicate_patients ?? 0)); ?>名
                （重複人数：<?php echo e($summary->duplicate_patients ?? 0); ?>名）<br>
                距離：
                <?php echo e((is_numeric($summary->total_distance) && $summary->total_distance != 0)
                                        ? (floor($summary->total_distance) == $summary->total_distance
                                            ? (int)$summary->total_distance
                                            : rtrim(rtrim($summary->total_distance, '0'), '.'))
                                        : ''); ?>km
            </p>
            <?php endif; ?>

            <div>
                <!-- <a href="<?php echo e(route('dashboard')); ?>" class="btn btn_back">戻る</a> -->
                <button type="button" class="btn_back" onclick="history.back()">戻る</button>
            </div>
        </section>
    </main>

</body>

</html>
<?php /**PATH C:\dev\tcpg_system_laravel\resources\views\staff_portal\home_visit\daily_report\index.blade.php ENDPATH**/ ?>