<div class="create-inline" id="bonus-create-inline" hidden>
  <div class="create-inline-head">
    <div class="create-inline-title">賞与データ作成</div>
    <div class="create-inline-actions">
      <button type="button" class="btn" id="bonus-create-select-all-btn">全選択</button>
      <button type="button" class="btn" id="bonus-create-clear-btn">解除</button>
      <button type="button" class="btn" id="bonus-create-close-btn">閉じる</button>
    </div>
  </div>
  <div class="create-inline-body">
    <div class="create-inline-row">
      <label for="bonus-create-payment-date">作成日</label>
      <input id="bonus-create-payment-date" type="date">
      <button type="button" class="btn" id="bonus-create-show-btn">表示</button>
      <button type="button" class="btn primary" id="bonus-create-submit-btn">作成</button>
      <button type="button" class="btn" id="bonus-delete-submit-btn">削除</button>
    </div>
    <div class="create-inline-note">未作成と作成済を一覧に表示します。作成は未作成のみ、削除は作成済のみ対象です。</div>
    <div class="create-inline-list" id="bonus-create-list"></div>
    <div class="create-inline-empty" id="bonus-create-empty" hidden>対象者はありません。</div>
  </div>
</div>
