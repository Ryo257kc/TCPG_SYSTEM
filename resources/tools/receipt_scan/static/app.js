(() => {
  const video = document.querySelector('#cameraPreview');
  const liveCameraStage = document.querySelector('#liveCameraStage');
  const canvas = document.querySelector('#capturedCanvas');
  const preview = document.querySelector('#photoPreview');
  const cornerEditor = document.querySelector('#cornerEditor');
  const correctedView = document.querySelector('#correctedView');
  const correctedPreview = document.querySelector('#correctedPreview');
  const overlay = document.querySelector('#cornerOverlay');
  const polygon = document.querySelector('#cornerPolygon');
  const handles = [...document.querySelectorAll('.corner-handle')];
  const message = document.querySelector('#message');
  const startButton = document.querySelector('#startButton');
  const captureButton = document.querySelector('#captureButton');
  const finishScanningButton = document.querySelector('#finishScanningButton');
  const correctButton = document.querySelector('#correctButton');
  const backButton = document.querySelector('#backButton');
  const confirmNextButton = document.querySelector('#confirmNextButton');
  const confirmFinishButton = document.querySelector('#confirmFinishButton');
  const retakeButton = document.querySelector('#retakeButton');
  const filePicker = document.querySelector('#filePicker');
  const saveFields = document.querySelector('#saveFields');
  const storeCandidate = document.querySelector('#storeCandidate');
  const dateCandidate = document.querySelector('#dateCandidate');
  const amountCandidate = document.querySelector('#amountCandidate');
  const filenameCandidate = document.querySelector('#filenameCandidate');
  const scanWorkflow = document.querySelector('#scanWorkflow');
  const outputView = document.querySelector('#outputView');
  const outputCount = document.querySelector('#outputCount');
  const outputMessage = document.querySelector('#outputMessage');
  const outputFormats = [...document.querySelectorAll('input[name="outputFormat"]')];
  const pdfFilenameField = document.querySelector('#pdfFilenameField');
  const pdfFilenameInput = document.querySelector('#pdfFilenameInput');
  const continueScanningButton = document.querySelector('#continueScanningButton');
  const saveOutputButton = document.querySelector('#saveOutputButton');
  const completionView = document.querySelector('#completionView');
  const restartScanningButton = document.querySelector('#restartScanningButton');
  const confirmedScansPanel = document.querySelector('#confirmedScansPanel');
  const currentScanActionsTitle = document.querySelector('#currentScanActionsTitle');
  const scanCount = document.querySelector('#scanCount');
  const currentScanStatus = document.querySelector('#currentScanStatus');
  const confirmedScanList = document.querySelector('#confirmedScanList');
  const wansHqButton = document.querySelector('#wansHqButton');
  const wansModal = document.querySelector('#wansModal');
  const wansModalClose = document.querySelector('#wansModalClose');
  let stream = null;
  let activeCorner = null;
  let activePointerId = null;
  let corners = [];
  let correctedImageDataUrl = '';
  const confirmedScans = [];
  let capturedCount = 0;
  let isEditingCurrentScan = false;
  let isRenameInputActive = false;
  let appView = 'scan';
  let isCameraActive = false;

  const localToday = () => {
    const today = new Date();
    return `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
  };
  const clearFields = () => {
    storeCandidate.value = ''; dateCandidate.value = localToday(); amountCandidate.value = '';
    filenameCandidate.textContent = '（候補なし）';
    saveFields.hidden = true; confirmNextButton.hidden = true; confirmFinishButton.hidden = true;
  };
  const stopCamera = () => { if (stream) { stream.getTracks().forEach((track) => track.stop()); stream = null; } };
  const showPhoto = (source) => {
    preview.src = source; cornerEditor.hidden = true; correctedView.hidden = true; correctedImageDataUrl = ''; isRenameInputActive = false;
    capturedCount += 1; isEditingCurrentScan = true; isCameraActive = false; updateConfirmedScansUi();
    clearFields(); confirmNextButton.hidden = true; confirmFinishButton.hidden = true; liveCameraStage.hidden = true; video.hidden = true; captureButton.hidden = true; correctButton.hidden = true;
    backButton.hidden = true; retakeButton.hidden = false; startButton.hidden = true; stopCamera();
    message.textContent = '画像の枠を確認してください。';
  };
  const drawCorners = () => {
    polygon.setAttribute('points', corners.map(({ x, y }) => `${x},${y}`).join(' '));
    handles.forEach((handle, index) => { handle.setAttribute('cx', corners[index].x); handle.setAttribute('cy', corners[index].y); });
  };
  const fallbackCorners = (width, height) => {
    const margin = Math.max(width, height) * 0.035;
    return [{ x: margin, y: margin }, { x: width - margin, y: margin }, { x: width - margin, y: height - margin }, { x: margin, y: height - margin }];
  };
  const polygonArea = (points) => Math.abs(points.reduce((sum, point, index) => {
    const next = points[(index + 1) % points.length]; return sum + point.x * next.y - point.y * next.x;
  }, 0)) / 2;
  const isConvex = (points) => {
    let direction = 0;
    for (let index = 0; index < 4; index += 1) {
      const a = points[index], b = points[(index + 1) % 4], c = points[(index + 2) % 4];
      const cross = (b.x - a.x) * (c.y - b.y) - (b.y - a.y) * (c.x - b.x);
      if (Math.abs(cross) < 0.001 || (direction && Math.sign(cross) !== direction)) return false;
      direction = Math.sign(cross);
    }
    return true;
  };
  const otsuThreshold = (gray) => {
    const histogram = new Uint32Array(256); gray.forEach((value) => { histogram[value] += 1; });
    let sum = 0, backCount = 0, backSum = 0, best = 0, bestVariance = -1;
    histogram.forEach((count, value) => { sum += count * value; });
    for (let threshold = 0; threshold < 256; threshold += 1) {
      backCount += histogram[threshold]; if (!backCount) continue;
      const foreCount = gray.length - backCount; if (!foreCount) break;
      backSum += threshold * histogram[threshold];
      const variance = backCount * foreCount * ((backSum / backCount) - ((sum - backSum) / foreCount)) ** 2;
      if (variance > bestVariance) { bestVariance = variance; best = threshold; }
    }
    return best;
  };
  const detectDocumentCorners = () => {
    const naturalWidth = preview.naturalWidth, naturalHeight = preview.naturalHeight;
    const scale = Math.min(1, 900 / Math.max(naturalWidth, naturalHeight));
    const width = Math.max(1, Math.round(naturalWidth * scale)), height = Math.max(1, Math.round(naturalHeight * scale));
    const analysisCanvas = document.createElement('canvas'); analysisCanvas.width = width; analysisCanvas.height = height;
    const context = analysisCanvas.getContext('2d', { willReadFrequently: true }); context.drawImage(preview, 0, 0, width, height);
    const pixels = context.getImageData(0, 0, width, height).data, gray = new Uint8Array(width * height);
    let brightnessSum = 0;
    for (let index = 0; index < gray.length; index += 1) { const p = index * 4; gray[index] = Math.round(pixels[p] * .299 + pixels[p + 1] * .587 + pixels[p + 2] * .114); brightnessSum += gray[index]; }
    const threshold = Math.min(245, Math.max(otsuThreshold(gray), Math.round(brightnessSum / gray.length) + 15));
    const bright = new Uint8Array(gray.length); for (let i = 0; i < gray.length; i += 1) bright[i] = gray[i] >= threshold ? 1 : 0;
    const stack = new Int32Array(bright.length), minimumPixels = Math.max(400, Math.round(bright.length * .06)); let best = null;
    for (let start = 0; start < bright.length; start += 1) {
      if (!bright[start]) continue;
      let stackSize = 1, count = 0, minX = width, maxX = 0, minY = height, maxY = 0;
      let minSum = { value: Infinity, x: 0, y: 0 }, maxSum = { value: -Infinity, x: 0, y: 0 }, minDiff = { value: Infinity, x: 0, y: 0 }, maxDiff = { value: -Infinity, x: 0, y: 0 };
      stack[0] = start; bright[start] = 0;
      while (stackSize) {
        const point = stack[--stackSize], x = point % width, y = Math.floor(point / width); count += 1;
        minX = Math.min(minX, x); maxX = Math.max(maxX, x); minY = Math.min(minY, y); maxY = Math.max(maxY, y);
        const sum = x + y, diff = x - y;
        if (sum < minSum.value) minSum = { value: sum, x, y }; if (sum > maxSum.value) maxSum = { value: sum, x, y };
        if (diff < minDiff.value) minDiff = { value: diff, x, y }; if (diff > maxDiff.value) maxDiff = { value: diff, x, y };
        const neighbours = [x ? point - 1 : -1, x < width - 1 ? point + 1 : -1, y ? point - width : -1, y < height - 1 ? point + width : -1];
        neighbours.forEach((next) => { if (next >= 0 && bright[next]) { bright[next] = 0; stack[stackSize++] = next; } });
      }
      if (count < minimumPixels || (minX === 0 && maxX === width - 1 && minY === 0 && maxY === height - 1)) continue;
      const candidate = [minSum, maxDiff, maxSum, minDiff].map(({ x, y }) => ({ x: x / scale, y: y / scale }));
      const aspect = (maxX - minX + 1) / (maxY - minY + 1), area = polygonArea(candidate) * scale * scale;
      if (aspect < .08 || aspect > 12 || !isConvex(candidate) || area < bright.length * .05) continue;
      const score = area + count * .25; if (!best || score > best.score) best = { score, corners: candidate };
    }
    return best?.corners ?? null;
  };
  const initializeCorners = () => {
    const width = preview.naturalWidth, height = preview.naturalHeight; if (!width || !height) return;
    const detected = detectDocumentCorners(); corners = detected || fallbackCorners(width, height);
    overlay.setAttribute('viewBox', `0 0 ${width} ${height}`); handles.forEach((handle) => handle.setAttribute('r', Math.max(width, height) * .045));
    isRenameInputActive = false; cornerEditor.hidden = false; correctButton.hidden = false; confirmNextButton.hidden = false; confirmFinishButton.hidden = false; drawCorners();
    message.textContent = detected ? '自動検出した枠を確認してください。必要に応じて調整できます。' : '自動検出できませんでした。枠を調整してください。';
  };
  const pointFromPointer = (event) => {
    const point = overlay.createSVGPoint(); point.x = event.clientX; point.y = event.clientY;
    const result = point.matrixTransform(overlay.getScreenCTM().inverse()), box = overlay.viewBox.baseVal;
    return { x: Math.max(0, Math.min(box.width, result.x)), y: Math.max(0, Math.min(box.height, result.y)) };
  };
  overlay.addEventListener('pointerdown', (event) => {
    const handle = event.target.closest('.corner-handle'); if (!handle) return;
    activeCorner = Number(handle.dataset.index); activePointerId = event.pointerId; handle.setPointerCapture(event.pointerId);
    corners[activeCorner] = pointFromPointer(event); drawCorners(); event.preventDefault();
  }, { passive: false });
  overlay.addEventListener('pointermove', (event) => { if (activeCorner === null || event.pointerId !== activePointerId) return; corners[activeCorner] = pointFromPointer(event); drawCorners(); event.preventDefault(); }, { passive: false });
  const endDrag = (event) => { if (activeCorner === null || event.pointerId !== activePointerId) return; const handle = handles[activeCorner]; if (handle.hasPointerCapture(event.pointerId)) handle.releasePointerCapture(event.pointerId); activeCorner = null; activePointerId = null; event.preventDefault(); };
  overlay.addEventListener('pointerup', endDrag, { passive: false }); overlay.addEventListener('pointercancel', endDrag, { passive: false }); preview.addEventListener('load', initializeCorners);

  const distance = (a, b) => Math.hypot(a.x - b.x, a.y - b.y);
  const solve = (matrix) => {
    for (let column = 0; column < 8; column += 1) { let pivot = column; for (let row = column + 1; row < 8; row += 1) if (Math.abs(matrix[row][column]) > Math.abs(matrix[pivot][column])) pivot = row;
      if (Math.abs(matrix[pivot][column]) < 1e-10) throw new Error('枠の位置を確認してください。'); [matrix[column], matrix[pivot]] = [matrix[pivot], matrix[column]];
      const divisor = matrix[column][column]; for (let item = column; item < 9; item += 1) matrix[column][item] /= divisor;
      for (let row = 0; row < 8; row += 1) { if (row === column) continue; const factor = matrix[row][column]; for (let item = column; item < 9; item += 1) matrix[row][item] -= factor * matrix[column][item]; }
    } return matrix.map((row) => row[8]);
  };
  const createCorrectedImage = () => {
    if (corners.length !== 4 || !isConvex(corners)) throw new Error('枠の位置を確認してください。');
    const outputWidth = Math.max(2, Math.round(Math.max(distance(corners[0], corners[1]), distance(corners[3], corners[2]))));
    const outputHeight = Math.max(2, Math.round(Math.max(distance(corners[0], corners[3]), distance(corners[1], corners[2]))));
    const sourceCanvas = document.createElement('canvas'); sourceCanvas.width = preview.naturalWidth; sourceCanvas.height = preview.naturalHeight;
    const sourceContext = sourceCanvas.getContext('2d', { willReadFrequently: true }); sourceContext.drawImage(preview, 0, 0);
    const source = sourceContext.getImageData(0, 0, sourceCanvas.width, sourceCanvas.height);
    const resultCanvas = document.createElement('canvas'); resultCanvas.width = outputWidth; resultCanvas.height = outputHeight;
    const resultContext = resultCanvas.getContext('2d'), result = resultContext.createImageData(outputWidth, outputHeight);
    const rect = [{ x: 0, y: 0 }, { x: outputWidth - 1, y: 0 }, { x: outputWidth - 1, y: outputHeight - 1 }, { x: 0, y: outputHeight - 1 }], matrix = [];
    rect.forEach(({ x, y }, index) => { const target = corners[index]; matrix.push([x, y, 1, 0, 0, 0, -target.x * x, -target.x * y, target.x], [0, 0, 0, x, y, 1, -target.y * x, -target.y * y, target.y]); });
    const [a, b, c, d, e, f, g, h] = solve(matrix);
    for (let y = 0; y < outputHeight; y += 1) for (let x = 0; x < outputWidth; x += 1) {
      const den = g * x + h * y + 1, sx = Math.max(0, Math.min(sourceCanvas.width - 1, (a * x + b * y + c) / den)), sy = Math.max(0, Math.min(sourceCanvas.height - 1, (d * x + e * y + f) / den));
      const left = Math.floor(sx), top = Math.floor(sy), right = Math.min(sourceCanvas.width - 1, left + 1), bottom = Math.min(sourceCanvas.height - 1, top + 1), horizontal = sx - left, vertical = sy - top, targetIndex = (y * outputWidth + x) * 4;
      for (let channel = 0; channel < 4; channel += 1) { const topValue = source.data[(top * sourceCanvas.width + left) * 4 + channel] * (1 - horizontal) + source.data[(top * sourceCanvas.width + right) * 4 + channel] * horizontal; const bottomValue = source.data[(bottom * sourceCanvas.width + left) * 4 + channel] * (1 - horizontal) + source.data[(bottom * sourceCanvas.width + right) * 4 + channel] * horizontal; result.data[targetIndex + channel] = topValue * (1 - vertical) + bottomValue * vertical; }
    }
    resultContext.putImageData(result, 0, 0); return resultCanvas.toDataURL('image/jpeg', .95);
  };
  const normalizeDate = (value) => { const match = value.match(/(20\d{2})\s*[./\-年]\s*(\d{1,2})\s*[./\-月]\s*(\d{1,2})(?:\s*日)?/) || value.match(/\b(20\d{2})(\d{2})(\d{2})\b/); return match ? `${match[1]}/${Number(match[2]).toString().padStart(2, '0')}/${Number(match[3]).toString().padStart(2, '0')}` : ''; };
  const validDate = (value) => { const normalized = normalizeDate(value); if (!normalized) return ''; const [year, month, day] = normalized.split('/').map(Number); return year >= 2000 && year <= 2099 && month >= 1 && month <= 12 && day >= 1 && day <= new Date(Date.UTC(year, month, 0)).getUTCDate() ? normalized : ''; };
  const sanitize = (value) => value.replace(/[\\/:*?"<>|\x00-\x1f]/g, '').replace(/\s+/g, '').replace(/[. ]+$/g, '');
  const defaultScanFilename = (date) => {
    const datePrefix = date.replace(/\D/g, '.'); let number = 1, filename = '';
    do { filename = `${datePrefix}-scan_${String(number).padStart(3, '0')}.jpg`; number += 1; } while (confirmedScans.some((scan) => sameFilename(scan.filename, filename)));
    return filename;
  };
  const updateFilename = () => { const date = validDate(dateCandidate.value), store = sanitize(storeCandidate.value), amount = amountCandidate.value.replace(/[^\d]/g, ''), formattedAmount = amount.replace(/\B(?=(\d{3})+(?!\d))/g, ','); filenameCandidate.textContent = date ? (store ? `${date.replace(/\D/g, '.')}-${store}${formattedAmount ? `_${formattedAmount}` : ''}.jpg` : defaultScanFilename(date)) : '（候補なし）'; };
  [storeCandidate, dateCandidate, amountCandidate].forEach((field) => field.addEventListener('input', updateFilename));
  const filenameFromFields = (useRenameValues) => {
    const date = validDate(dateCandidate.value), store = useRenameValues ? sanitize(storeCandidate.value) : '', amount = useRenameValues ? amountCandidate.value.replace(/[^\d]/g, '') : '', formattedAmount = amount.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    if (!date) throw new Error('日付を確認してから確定してください。');
    return store ? `${date.replace(/\D/g, '.')}-${store}${formattedAmount ? `_${formattedAmount}` : ''}.jpg` : defaultScanFilename(date);
  };
  const correctedJpegBlob = () => {
    if (!correctedImageDataUrl?.startsWith('data:image/jpeg;base64,')) throw new Error('補正後JPEGが見つかりません。');
    const binary = atob(correctedImageDataUrl.split(',', 2)[1]), bytes = new Uint8Array(binary.length); for (let i = 0; i < binary.length; i += 1) bytes[i] = binary.charCodeAt(i);
    return new Blob([bytes], { type: 'image/jpeg' });
  };
  const createShareFile = (useRenameValues) => {
    return new File([correctedJpegBlob()], filenameFromFields(useRenameValues), { type: 'image/jpeg' });
  };
  const download = (file) => { const url = URL.createObjectURL(file), link = document.createElement('a'); link.href = url; link.download = file.name; link.click(); window.setTimeout(() => URL.revokeObjectURL(url), 1000); };
  const sameFilename = (first, second) => first.localeCompare(second, 'ja-JP', { sensitivity: 'accent' }) === 0;
  const createId = () => (window.crypto?.randomUUID ? window.crypto.randomUUID() : `${Date.now()}-${Math.random()}`);
  const defaultPdfFilename = () => `${localToday().replace(/-/g, '.')}-`;
  const setAppView = (view) => {
    appView = view;
    scanWorkflow.hidden = view !== 'scan'; outputView.hidden = view !== 'output'; completionView.hidden = view !== 'complete';
    updateConfirmedScansUi();
  };
  const updateConfirmedScansUi = () => {
    currentScanActionsTitle.textContent = isEditingCurrentScan ? '撮影した書類を確認' : '書類を撮影';
    scanCount.textContent = `確定済み ${confirmedScans.length}件`;
    currentScanStatus.hidden = !isEditingCurrentScan;
    currentScanStatus.textContent = isEditingCurrentScan ? `現在 ${confirmedScans.length + 1}枚目を編集中` : '';
    finishScanningButton.textContent = `撮影終了（確定済み ${confirmedScans.length}件）`;
    finishScanningButton.hidden = !(appView === 'scan' && isCameraActive && !isEditingCurrentScan && confirmedScans.length);
    outputCount.textContent = `確定済み ${confirmedScans.length}件`;
    confirmedScansPanel.hidden = !(appView === 'output' && confirmedScans.length);
    saveOutputButton.disabled = confirmedScans.length === 0;
    confirmedScanList.replaceChildren();
    confirmedScans.forEach((scan, index) => {
      const item = document.createElement('li'), details = document.createElement('div'), thumbnail = document.createElement('img'), name = document.createElement('span'), remove = document.createElement('button');
      const thumbnailUrl = URL.createObjectURL(scan.imageBlob);
      thumbnail.className = 'confirmed-scan-thumbnail'; thumbnail.src = thumbnailUrl; thumbnail.alt = `確定済み ${index + 1}件目のプレビュー`;
      thumbnail.addEventListener('load', () => URL.revokeObjectURL(thumbnailUrl), { once: true });
      thumbnail.addEventListener('error', () => URL.revokeObjectURL(thumbnailUrl), { once: true });
      details.className = 'confirmed-scan-details'; name.className = 'confirmed-scan-name'; name.textContent = `${index + 1}. ${scan.filename}`;
      remove.type = 'button'; remove.className = 'confirmed-scan-delete'; remove.textContent = '削除';
      remove.addEventListener('click', () => { confirmedScans.splice(index, 1); updateConfirmedScansUi(); outputMessage.textContent = '確定済みレシートを削除しました。'; });
      details.append(thumbnail, name); item.append(details, remove); confirmedScanList.append(item);
    });
  };
  const shareFiles = async (files) => {
    const data = { files };
    if (!navigator.share || !navigator.canShare || !navigator.canShare(data)) throw new Error('このブラウザでは、複数ファイルの保存に対応していません。');
    await navigator.share(data);
  };
  const blobToDataUrl = (blob) => new Promise((resolve, reject) => {
    const reader = new FileReader(); reader.addEventListener('load', () => resolve(reader.result)); reader.addEventListener('error', () => reject(new Error('PDF用画像を読み込めませんでした。'))); reader.readAsDataURL(blob);
  });
  const imageDimensions = (dataUrl) => new Promise((resolve, reject) => {
    const image = new Image(); image.addEventListener('load', () => resolve({ width: image.naturalWidth, height: image.naturalHeight })); image.addEventListener('error', () => reject(new Error('PDF用画像のサイズを取得できませんでした。'))); image.src = dataUrl;
  });
  const createPdfFile = async () => {
    if (!window.jspdf?.jsPDF) throw new Error('PDF作成ライブラリを読み込めませんでした。');
    const filename = sanitize(pdfFilenameInput.value);
    if (!filename) throw new Error('PDFファイル名を入力してください。');
    const { jsPDF } = window.jspdf, pageForImage = (properties) => {
      const scale = 1000 / Math.max(properties.width, properties.height);
      const width = properties.width * scale, height = properties.height * scale;
      return { width, height, orientation: width >= height ? 'landscape' : 'portrait' };
    };
    let document = null;
    for (let index = 0; index < confirmedScans.length; index += 1) {
      const image = await blobToDataUrl(confirmedScans[index].imageBlob), properties = await imageDimensions(image);
      const page = pageForImage(properties);
      if (index === 0) document = new jsPDF({ orientation: page.orientation, unit: 'pt', format: [page.width, page.height], compress: true });
      else document.addPage([page.width, page.height], page.orientation);
      document.addImage(image, 'JPEG', 0, 0, page.width, page.height);
    }
    return new File([document.output('blob')], `${filename}.pdf`, { type: 'application/pdf' });
  };
  correctButton.addEventListener('click', () => { correctButton.disabled = true; requestAnimationFrame(() => { try { correctedImageDataUrl = createCorrectedImage(); isRenameInputActive = true; correctedPreview.src = correctedImageDataUrl; cornerEditor.hidden = true; correctedView.hidden = false; correctButton.hidden = true; backButton.hidden = false; saveFields.hidden = false; confirmNextButton.hidden = false; confirmFinishButton.hidden = false; updateFilename(); message.textContent = '日付を確認し、店名・金額は必要に応じて入力して確定してください。'; } catch (error) { message.textContent = error.message || '枠の位置を確認してください。'; } finally { correctButton.disabled = false; } }); });
  backButton.addEventListener('click', () => { isRenameInputActive = false; correctedView.hidden = true; cornerEditor.hidden = false; correctButton.hidden = false; backButton.hidden = true; saveFields.hidden = true; confirmNextButton.hidden = false; confirmFinishButton.hidden = false; message.textContent = '枠を調整するか、リネームする、またはそのまま確定してください。'; });
  const confirmCurrentScan = async (finishAfterConfirm) => {
    confirmNextButton.disabled = true; confirmFinishButton.disabled = true;
    try {
      const useRenameValues = isRenameInputActive;
      if (!useRenameValues) correctedImageDataUrl = createCorrectedImage();
      const file = createShareFile(useRenameValues);
      if (confirmedScans.some((scan) => sameFilename(scan.filename, file.name))) throw new Error('同名のファイル名がすでに確定されています。店名・日付・金額を確認してください。');
      confirmedScans.push({
        id: createId(), filename: file.name, imageBlob: file.slice(0, file.size, 'image/jpeg'),
        date: dateCandidate.value, store: useRenameValues ? storeCandidate.value : '', amount: useRenameValues ? amountCandidate.value.replace(/[^\d]/g, '') : '',
        corners: corners.map(({ x, y }) => ({ x, y })),
      });
      isEditingCurrentScan = false; isRenameInputActive = false; updateConfirmedScansUi();
      correctedView.hidden = true; cornerEditor.hidden = true; backButton.hidden = true; saveFields.hidden = true; confirmNextButton.hidden = true; confirmFinishButton.hidden = true;
      if (finishAfterConfirm) {
        openOutputView();
      } else {
        startNativeCapture();
        message.textContent = `確定しました。次のレシートを撮影してください（確定済み: ${confirmedScans.length}件）。`;
      }
    } catch (error) {
      message.textContent = error.message || '確定できませんでした。';
    } finally {
      confirmNextButton.disabled = false; confirmFinishButton.disabled = false;
    }
  };
  confirmNextButton.addEventListener('click', () => confirmCurrentScan(false));
  confirmFinishButton.addEventListener('click', () => confirmCurrentScan(true));
  const openOutputView = () => {
    stopCamera(); isCameraActive = false; liveCameraStage.hidden = true; video.hidden = true; captureButton.hidden = true;
    outputMessage.textContent = ''; updateOutputFormatUi(); setAppView('output');
  };
  const updateOutputFormatUi = () => {
    const isPdf = outputFormats.find((field) => field.checked)?.value === 'pdf';
    pdfFilenameField.hidden = !isPdf;
    if (isPdf && !pdfFilenameInput.value) pdfFilenameInput.value = defaultPdfFilename();
  };
  finishScanningButton.addEventListener('click', () => {
    if (!confirmedScans.length) return;
    openOutputView();
  });
  continueScanningButton.addEventListener('click', async () => {
    outputMessage.textContent = ''; setAppView('scan'); startNativeCapture();
  });
  outputFormats.forEach((field) => field.addEventListener('change', updateOutputFormatUi));
  saveOutputButton.addEventListener('click', async () => {
    saveOutputButton.disabled = true; outputMessage.textContent = '';
    try {
      if (!confirmedScans.length) throw new Error('確定済みレシートがありません。');
      const format = outputFormats.find((field) => field.checked)?.value;
      const files = format === 'pdf'
        ? [await createPdfFile()]
        : confirmedScans.map((scan) => new File([scan.imageBlob], scan.filename, { type: 'image/jpeg' }));
      await shareFiles(files);
      setAppView('complete');
    } catch (error) {
      outputMessage.textContent = error?.name === 'AbortError' ? '保存をキャンセルしました。' : (error.message || '保存を開始できませんでした。');
    } finally {
      saveOutputButton.disabled = confirmedScans.length === 0;
    }
  });
  restartScanningButton.addEventListener('click', async () => {
    stopCamera(); confirmedScans.splice(0, confirmedScans.length); capturedCount = 0; corners = []; correctedImageDataUrl = '';
    isEditingCurrentScan = false; isRenameInputActive = false; isCameraActive = false; outputMessage.textContent = ''; pdfFilenameInput.value = defaultPdfFilename();
    outputFormats.find((field) => field.value === 'jpeg').checked = true; updateOutputFormatUi();
    correctedView.hidden = true; cornerEditor.hidden = true; liveCameraStage.hidden = true; video.hidden = true; captureButton.hidden = true;
    backButton.hidden = true; retakeButton.hidden = true; correctButton.hidden = true; startButton.hidden = false; clearFields();
    setAppView('scan'); startNativeCapture();
  });
  const startCamera = async () => { clearFields(); isEditingCurrentScan = false; isRenameInputActive = false; isCameraActive = false; corners = []; correctedImageDataUrl = ''; preview.removeAttribute('src'); correctedPreview.removeAttribute('src'); cornerEditor.hidden = true; correctedView.hidden = true; correctButton.hidden = true; backButton.hidden = true; retakeButton.hidden = true; updateConfirmedScansUi(); if (!navigator.mediaDevices?.getUserMedia) { filePicker.click(); return; } try { stopCamera(); stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' }, width: { ideal: 2560 }, height: { ideal: 1440 } }, audio: false }); video.srcObject = stream; liveCameraStage.hidden = false; video.hidden = false; isCameraActive = true; startButton.hidden = true; captureButton.hidden = false; retakeButton.hidden = true; updateConfirmedScansUi(); message.textContent = 'レシートを写して、画面をタップするか「撮影」を押してください。'; } catch { filePicker.click(); } };
  const startNativeCapture = () => { clearFields(); isEditingCurrentScan = false; isRenameInputActive = false; isCameraActive = false; corners = []; correctedImageDataUrl = ''; stopCamera(); preview.removeAttribute('src'); correctedPreview.removeAttribute('src'); cornerEditor.hidden = true; correctedView.hidden = true; liveCameraStage.hidden = true; video.hidden = true; captureButton.hidden = true; correctButton.hidden = true; backButton.hidden = true; retakeButton.hidden = true; startButton.hidden = false; updateConfirmedScansUi(); filePicker.click(); };
  startButton.addEventListener('click', startNativeCapture); retakeButton.addEventListener('click', startNativeCapture);
  const capturePhoto = () => { if (video.hidden || !video.videoWidth || !video.videoHeight) return; canvas.width = video.videoWidth; canvas.height = video.videoHeight; canvas.getContext('2d').drawImage(video, 0, 0); showPhoto(canvas.toDataURL('image/jpeg', .95)); };
  captureButton.addEventListener('click', capturePhoto);
  filePicker.addEventListener('change', () => { const [file] = filePicker.files; if (!file) return; const reader = new FileReader(); reader.addEventListener('load', () => showPhoto(reader.result)); reader.readAsDataURL(file); filePicker.value = ''; });

  const openWansModal = () => {
    wansModal.hidden = false;
    document.body.classList.add('wans-modal-open');
    wansModalClose.focus();
  };

  const closeWansModal = () => {
    wansModal.hidden = true;
    document.body.classList.remove('wans-modal-open');
    wansHqButton.focus();
  };

  wansHqButton.addEventListener('click', openWansModal);
  wansModalClose.addEventListener('click', closeWansModal);
  wansModal.addEventListener('click', (event) => {
    if (event.target.matches('[data-wans-close]')) closeWansModal();
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !wansModal.hidden) closeWansModal();
  });
  clearFields();
  pdfFilenameInput.value = defaultPdfFilename();
  updateOutputFormatUi();
  updateConfirmedScansUi();
  window.addEventListener('beforeunload', stopCamera);
})();
