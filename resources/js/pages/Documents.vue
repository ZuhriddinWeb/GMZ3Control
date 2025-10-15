<template>
  <div class="grid grid-rows-[55px,1fr]">
    <main></main>
    <main class="flex-grow mt-10">
      <VaDateInput v-model="value" class="mb-10" onchange="fetchData(value)" />
      <VaTabs v-model="activeTab" class="mb-6">
        <VaTab v-for="p in pages" :key="p.NumberPage" :name="p.NumberPage" @click="onTabClick(p.NumberPage)">
          {{ p.Name }}
        </VaTab>
      </VaTabs>
      <div ref="sheetEl" style="width:100vw;height:100vh;"></div>
      <!-- PRELOADER OVERLAY -->
      <div v-if="saving" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center">
        <div class="bg-white rounded-xl p-6 w-[360px] text-center shadow-lg">
          <div class="text-lg font-semibold mb-3">Keshga yozilmoqda…</div>

          <div class="w-full h-2 bg-gray-200 rounded overflow-hidden">
            <div class="h-2 rounded" :style="{ width: savePercent + '%', background: '#3b82f6' }"></div>
          </div>
          <div class="mt-2 text-sm text-gray-600">{{ savePercent }}%</div>

          <div v-if="saveError" class="mt-3 text-red-600 text-sm">
            {{ saveError }}
          </div>
          <button v-if="saveError" class="mt-4 px-4 py-2 rounded bg-blue-600 text-white" @click="retrySave">
            Qayta urinish
          </button>
        </div>
      </div>

    </main>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue'
import jspreadsheet from 'jspreadsheet-ce'
import 'jspreadsheet-ce/dist/jspreadsheet.css'
import 'jsuites/dist/jsuites.css'
import axios from 'axios'
import { useRouter } from 'vue-router'
const router = useRouter()
const value = ref(new Date())
const sheetEl = ref(null)
const rowData = ref([]);
const pages = ref([])               // [{ Name, NumberPage }, ...]
const activeTab = ref(null)         // hozirgi NumberPage
// VUE o'zgaruvchilar

let jexcel = null
const docId = 300 // yoki props/route’dan oling

// --- PRELOADER HOLATI
const saving = ref(false)
const savePercent = ref(0)
const saveError = ref('')
let _lastSaveArgs = null  // retry uchun oxirgi chaqiruv parametrlari

function sleep(ms) { return new Promise(r => setTimeout(r, ms)) }
function chunk(arr, size) { const out = []; for (let i = 0; i < arr.length; i += size) out.push(arr.slice(i, i + size)); return out }


const fetchDataPages = async () => {
  try {
    const { data } = await axios.get(`/getRowPage/${22}`)
    // backend: massiv { Name, NumberPage }
    pages.value = Array.isArray(data) ? data : (data.items || [])
    if (pages.value.length && !activeTab.value) {
      activeTab.value = pages.value[0].NumberPage
      await fetchData(activeTab.value, value.value)
    }
  } catch (e) {
    console.error('getRowPage error', e)
  }
}
fetchDataPages()
const fetchData = async (numberPage, date) => {
  try {
    const { data } = await axios.get(`/selectResultBlogs/${numberPage}/${formatDate(date)}`)
    rowData.value = data
  } catch (e) {
    console.error('selectResultBlogs error', e)
  }
}
const onTabClick = async (numberPage) => {
  // ← NEW: 303 bo'lsa GMZ3 sahifasiga yo‘naltiramiz (sana query bilan)
  if (Number(numberPage) === 303) {
    router.push({ name: 'gmz3-report', query: { date: formatDate(value.value) } })
    return
  }
  else if (Number(numberPage) === 301) {
    router.push({ name: 'gmz3-report301', query: { date: formatDate(value.value) } })
    return
  }
  activeTab.value = numberPage
  await fetchData(activeTab.value, value.value)
}
function getFirstGroupName() {
  // JSON kelmagan bo‘lsa yoki bo‘sh bo‘lsa
  if (!rowData.value || typeof rowData.value !== 'object') return ''

  const groupIds = Object.keys(rowData.value || {})
  if (!groupIds.length) return ''

  const firstGroup = rowData.value[groupIds[0]]
  if (!firstGroup) return ''

  const changeIds = Object.keys(firstGroup)
  if (!changeIds.length) return ''

  const firstChange = firstGroup[changeIds[0]]
  if (!firstChange) return ''

  const timeIds = Object.keys(firstChange)
  if (!timeIds.length) return ''

  const firstTime = firstChange[timeIds[0]]
  if (!firstTime || !firstTime.length) return ''

  const groupName = firstTime[0].group_name
  return groupName || ''
}

const groupName = getFirstGroupName()
if (jexcel && groupName) {
  jexcel.setValue('A3', groupName)
}

// function updateCells() {
//   if (!jexcel) return
//   jexcel.setValueFromCoords(0, 0, a1.value)        // A1 (0,0)
//   jexcel.setValueFromCoords(1, 0, d1.value)        // D1 (1,0)
//   c1.value = a1.value + d1.value
//   jexcel.setValueFromCoords(2, 0, c1.value)        // C1 (2,0)
// }

const style = {
  A1: "border-left:2px solid #000;border-right:1px solid #fff;border-top:1px solid #000;border-bottom:1px solid #000;w-200;",
  A2: "background:yellow",
  B2: "border-right:none;border-left:none;background:yellow;font-weight: bold;",
  C2: "border-right:none;border-left:none;background:yellow;font-weight: bold;",
  D2: "border-right:none;border-left:none;background:yellow;font-weight: bold;",
  E2: "border-right:none;border-left:none;background:yellow;font-weight: bold;",
  F2: "border-right:none;border-left:none;background:yellow;font-weight: bold;",
  G2: "border-right:none;border-left:none;background:yellow;font-weight: bold;",
  H2: "border-right:none;border-left:none;background:yellow;font-weight: bold;",
  I2: "border-right:none;border-left:none;background:yellow;font-weight: bold;",
  A3: "border-right:none;border-left:none;font-weight: bold;",
  B3: "border-right:none;border-left:none;font-weight: bold;",
  C3: "border-right:none;border-left:none;font-weight: bold;",

  A4: "font-weight: bold;",
  B4: "font-weight: bold;",
  C4: "color:red;font-weight: bold;",
  // A3: "background: #c7efc4; font-weight: bold;", // Yashil
  // B3: "background: #c7efc4; font-weight: bold;", // Yashil
  // C3: "background: #c7efc4; font-weight: bold;", // Yashil
}


function formatDate(date) {
  if (!date) return '';
  const d = new Date(date)
  const day = String(d.getDate()).padStart(2, '0')
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const year = d.getFullYear()
  return `${day}.${month}.${year}`
}
// function getGroupParameterNames() {
//   // rowData.value bu yerda API natija (object)
//   if (!rowData.value || typeof rowData.value !== 'object') return []

//   const groupIds = Object.keys(rowData.value)
//   if (!groupIds.length) return []

//   const firstGroup = rowData.value[groupIds[0]]
//   const changeIds = Object.keys(firstGroup)
//   if (!changeIds.length) return []

//   const firstChange = firstGroup[changeIds[0]]
//   const timeIds = Object.keys(firstChange)
//   if (!timeIds.length) return []

//   // Birinchi time id array ichidagi barcha parametrlar
//   const paramArr = firstChange[timeIds[0]]
//   if (!Array.isArray(paramArr)) return []

//   // Faqat parameter_name larini arrayda qaytaradi
//   return paramArr.map(x => x.parameter_name)
// }
// function getFirstSmenaTimeNames() {
//   if (!rowData.value || typeof rowData.value !== 'object') return []
//   const groupIds = Object.keys(rowData.value)
//   if (!groupIds.length) return []

//   const firstGroup = rowData.value[groupIds[0]]
//   const changeIds = Object.keys(firstGroup)
//   if (!changeIds.length) return []

//   const firstChange = firstGroup[changeIds[0]]  // Birinchi smena
//   const timeIds = Object.keys(firstChange)
//   if (!timeIds.length) return []

//   // Har bir timeId bo‘yicha, time_name ni arrayda qaytaradi
//   return timeIds.map((tid) => {
//     const arr = firstChange[tid]
//     // arrayda birinchi objectdan time_name olamiz (barcha paramlar uchun bir xil bo‘ladi)
//     return Array.isArray(arr) && arr[0]?.time_name ? arr[0].time_name : ''
//   })
// }

onMounted(() => {
  fetchData(value.value)
  // Boshlang'ich data (faqat birinchi qator)
  const data = [
    [formatDate(value.value), ...Array(18).fill('')],
    Array(300).fill('') // 1-qator
  ]
  // sarlavha ustunlar
  function getColName(index) {
    let name = ''
    let n = index
    while (n >= 0) {
      name = String.fromCharCode((n % 26) + 65) + name
      n = Math.floor(n / 26) - 1
    }
    return name
  }
  const columns = Array.from({ length: 300 }, (_, i) => ({
    title: getColName(i),
    width: i === 0 ? 150 : 100
  }))

  jexcel = jspreadsheet(sheetEl.value, {
    data,
    style,
    columns,
    minDimensions: [150, 500],
    merge: [
      { row: 0, col: 0, rowspan: 1, colspan: 14 }, // A1:N1
      { row: 1, col: 0, rowspan: 1, colspan: 14 }, // A2:N2
    ],
    onchange: (instance, cell, x, y, value) => {
      const colLetter = getColLetter(x)
      const rowNum = y + 1
    }
  });
  // Sarlavhalar matnini ham yozib qo‘ying:
  jexcel.setValue('A1', formatDate(value.value))
  jexcel.setValue('C2', 'OTK')
  jexcel.setValue('D2', '')
  jexcel.setValue('E2', 'laboratoriyasi')
  // jexcel.setValue('F2', 'suv')
  jexcel.setValue('G2', 'tahlili')
})
function formatShortTime(timeStr) {
  // "08:00:00.0000000" → "08:00"
  if (!timeStr) return ''
  const parts = timeStr.split(':')
  return parts[0] + ':' + parts[1]
}

function roundValue(val) {
  const n = Number(val)
  if (isNaN(n)) return val
  return n.toFixed(2)
}

function getColLetter(x) {
  let col = ''
  let n = x
  while (n >= 0) {
    col = String.fromCharCode((n % 26) + 65) + col
    n = Math.floor(n / 26) - 1
  }
  return col
}

function setTableBorder(jexcel, startRow, startCol, rowCount, colCount, borderColor = '#d10000') {
  const endRow = startRow + rowCount - 1;
  const endCol = startCol + colCount - 1;
  let borderStyle = {};

  // Har bir qatordagi chap va o‘ng border
  for (let row = startRow; row <= endRow; row++) {
    borderStyle[`${getColLetter(startCol)}${row + 1}`] = `border-left: 2px solid ${borderColor};`;
    borderStyle[`${getColLetter(endCol)}${row + 1}`] = `border-right: 2px solid ${borderColor};`;
  }
  // Har bir ustundagi yuqori va pastki border
  for (let col = startCol; col <= endCol; col++) {
    const letter = getColLetter(col);
    borderStyle[`${letter}${startRow + 1}`] = `border-top: 2px solid ${borderColor};`;
    borderStyle[`${letter}${endRow + 1}`] = `border-bottom: 2px solid ${borderColor};`;
  }
  jexcel.setStyle(borderStyle);
}
// Sana -> API format (dd.MM.yyyy)
function apiDate(d) {
  const x = new Date(d)
  const dd = String(x.getDate()).padStart(2, '0')
  const mm = String(x.getMonth() + 1).padStart(2, '0')
  const yyyy = x.getFullYear()
  return `${dd}.${mm}.${yyyy}`
}

// "B6" -> { x:1, y:5 } (0-based)
function parseCellAddress(addr) {
  const m = String(addr).toUpperCase().match(/^([A-Z]+)(\d+)$/)
  if (!m) return null
  const letters = m[1], row = parseInt(m[2], 10) - 1
  let x = 0
  for (let i = 0; i < letters.length; i++) {
    x = x * 26 + (letters.charCodeAt(i) - 64)
  }
  return { x: x - 1, y: row }
}
// Kerak bo‘lgan kataklar ro‘yxati (xohlasangiz sahifa bo‘yicha override qiling)
// --- YORDAMCHI: foydalanilgan kataklarni to‘liq yig‘ish (bo‘sh bo‘lmaganlar)
function getUsedCellsAsItems() {
  if (!jexcel || !jexcel.getData) return []

  const data = jexcel.getData() // 2D massiv
  let maxRow = -1, maxCol = -1

  // Foydalanilgan chekka (bounding box) ni topamiz
  for (let y = 0; y < data.length; y++) {
    const row = data[y] || []
    for (let x = 0; x < row.length; x++) {
      const v = row[x]
      if (v !== '' && v !== null && v !== undefined) {
        if (y > maxRow) maxRow = y
        if (x > maxCol) maxCol = x
      }
    }
  }

  if (maxRow < 0 || maxCol < 0) return []
  // Chekka bo‘yicha qiymatlarni yig‘amiz
  const items = []
  for (let y = 0; y <= maxRow; y++) {
    for (let x = 0; x <= maxCol; x++) {
      const v = jexcel.getValueFromCoords(x, y)
      if (v === '' || v === null || v === undefined) continue
      // A1, B5 ... ko‘rinishida manzil
      const addr = `${getColLetter(x)}${y + 1}`
      // Raqamlash (vergulni nuqtaga aylantirib)
      const num = Number(String(v).replace(',', '.'))
      items.push({ cell: addr, value: Number.isFinite(num) ? num : null })
      // Agar matnlarni ham saqlamoqchi bo‘lsangiz, backend sxemasi kerak bo‘ladi (value_text)
    }
  }
  return items
}
// --- Butun jadvalni keshga jo‘natish (progress bilan)
async function cacheWholeSheet(numberPage, dateObj) {
  const items = getUsedCellsAsItems()
  if (!items.length) {
    // bo'sh bo'lsa ham ko'rsatkichni tozalab qo'yamiz
    saving.value = false
    savePercent.value = 100
    return
  }

  saving.value = true
  savePercent.value = 0
  saveError.value = ''
  _lastSaveArgs = { numberPage, dateObj } // retry uchun saqlab qo'yamiz

  const total = items.length
  const BATCH = 200      // partiya o‘lchami (xohlasangiz 500/1000)
  const parts = chunk(items, BATCH)

  let processed = 0
  for (let i = 0; i < parts.length; i++) {
    const batch = parts[i]
    try {
      await axios.post('/sheet/values/bulk', {
        numberPage: Number(numberPage),
        date: apiDate(dateObj),
        items: batch,
      })
      processed += batch.length
      // % ni yangilaymiz
      savePercent.value = Math.min(100, Math.round((processed / total) * 100))
      await nextTick()  // UI'ni yangilashga imkon
      // (ixtiyoriy) serverni haddan ortiq urmaslik uchun ozgina pauza:
      // await sleep(10)
    } catch (err) {
      console.error('bulk save error', err?.response?.data || err)
      saveError.value = 'Saqlashda xatolik. "Qayta urinish" tugmasini bosing.'
      // ❗ Diqqat: yopmaymiz — foydalanuvchi retry bosishi kerak
      return
    }
  }
  // hammasi muvaffaqiyatli tugadi
  savePercent.value = 100
  await sleep(150) // progress 100% ko‘rinib turishi uchun kichik pauza
  saving.value = false
}

async function retrySave() {
  if (!_lastSaveArgs) return
  // boshidan yozamiz (xohlasangiz processed indeksdan davom ettiradigan murakkabroq variant ham qilsa bo‘ladi)
  await cacheWholeSheet(_lastSaveArgs.numberPage, _lastSaveArgs.dateObj)
}


function getCellsToCache(page) {
  return cellsToCacheByPage[page] || cellsToCacheByPage.default
}
// JSpreadsheetdan qiymat(lar)ni olib, /sheet/values/bulk ga yozish
async function cacheSheetValues(numberPage, dateObj, cells) {
  if (!jexcel || !Array.isArray(cells) || !cells.length) return
  const items = []

  for (const addr of cells) {
    const pos = parseCellAddress(addr)
    if (!pos) continue
    const v = jexcel.getValueFromCoords(pos.x, pos.y)
    const n = Number(v)
    items.push({ cell: addr, value: isFinite(n) ? n : null })
  }

  if (!items.length) return
  await axios.post('/sheet/values/bulk', {
    numberPage: Number(numberPage),
    date: apiDate(dateObj),
    items,
  })
}
// --- Jadval chizish tugagach: tanlangan har qanday sahifa uchun keshga yozamiz
setTimeout(() => {
  const page = Number(activeTab.value)
  if (!page) return
  const cells = getCellsToCache(page)
  cacheSheetValues(page, value.value, cells)
    .catch(err => console.error('cacheSheetValues error', err))
}, 0)


//fetchData(value.value)
// Agar a1 yoki d1 o‘zgarsa, excelda ham o‘zgaradi
// watch(value, (val) => {
//   if (jexcel) {
//     jexcel.setValue('A1', formatDate(val))
//     fetchData(val)
//   }
// })

watch(value, (val) => {
  if (activeTab.value) {
    fetchData(activeTab.value, val)
  }
})
watch(rowData, () => {
  console.log('Qabul qilingan rowData:', rowData.value)
  let groupName = getFirstGroupName()
  console.log('Aniqlangan groupName:', groupName)
  if (jexcel && groupName) {
    jexcel.setValue('A3', groupName)
  }

})
watch(rowData, async () => {
  const groupIds = Object.keys(rowData.value || {});
  if (!jexcel || !groupIds.length) return;

  let currentStartRow = 2;
  let currentStartCol = 0;
  let jadvallarInRow = 0;

  groupIds.forEach((groupId) => {
    const group = rowData.value[groupId];
    const changeIds = Object.keys(group);

    // Har bir group uchun maksimal balandlikni hisoblab boramiz
    let groupMaxRowCount = 0;

    changeIds.forEach((changeId) => {
      const change = group[changeId];
      const timeIds = Object.keys(change);

      // 1) Header uchun unikal parametrlar (faqat birinchi time’dan emas, butun change’dan)
      const allParams = new Set();
      timeIds.forEach(tid => {
        const arr = change[tid] || [];
        arr.forEach(x => allParams.add(x.parameter_name));
      });
      const parameterNames = Array.from(allParams);

      // 2) Vaqtlarni unikal qilib, sortlab olamiz (08:00 bir marta)
      const uniqueTimes = Array.from(new Set(
        timeIds.map(tid => {
          const arr = change[tid] || [];
          return arr[0]?.time_name || '';
        })
      )).sort(); // kerak bo‘lsa o‘z sortingiz

      const colCount = parameterNames.length + 1;      // +1 — “Vaqt” ustuni
      const rowCount = uniqueTimes.length + 3;         // 3 — group/smena/header qatorlari

      groupMaxRowCount = Math.max(groupMaxRowCount, rowCount);

      // 3) Sarlavha qismi
      jexcel.setValueFromCoords(currentStartCol, currentStartRow, change[timeIds[0]]?.[0]?.group_name || '');
      jexcel.setValueFromCoords(currentStartCol, currentStartRow + 1, `${changeId}-Smena`);

      // ranglar
      const headerStyle = {};
      for (let i = 0; i < colCount; i++) {
        const gCell = `${getColLetter(currentStartCol + i)}${currentStartRow + 1}`;
        headerStyle[gCell] = 'background: #c7efc4; font-weight: bold;';
        const sCell = `${getColLetter(currentStartCol + i)}${currentStartRow + 2}`;
        headerStyle[sCell] = 'background: #cfe2f3; font-weight: bold;';
        const hCell = `${getColLetter(currentStartCol + i)}${currentStartRow + 3}`;
        headerStyle[hCell] = 'background: #c9b2d6; font-weight: bold;';
      }
      setTimeout(() => jexcel.setStyle(headerStyle), 10);

      // 4) Header hujayralari: “Vaqt” + parametr nomlari
      jexcel.setValueFromCoords(currentStartCol, currentStartRow + 2, "Vaqt");
      parameterNames.forEach((p, i) => {
        jexcel.setValueFromCoords(currentStartCol + 1 + i, currentStartRow + 2, p);
      });

      // 5) Parametr nomidan kolonkaga index xarita
      const colIndexByParam = new Map();
      parameterNames.forEach((p, i) => colIndexByParam.set(p, i));

      // 6) Vaqt qatorlarini to‘ldirish
      uniqueTimes.forEach((tName, rIdx) => {
        const row = currentStartRow + 3 + rIdx;

        // Vaqt ustuni (format qisqartirilgan)
        jexcel.setValueFromCoords(currentStartCol, row, formatShortTime(tName));
        const vaqtCell = jexcel.getCellFromCoords(currentStartCol, row);
        if (vaqtCell) {
          vaqtCell.style.backgroundColor = "#bbfcc1";
          vaqtCell.style.fontWeight = "bold";
        }

        // Shu vaqtga tegishli barcha yozuvlar (bir nechta timeId bo‘lishi mumkin — birlashtiramiz)
        const itemsForTime = [];
        timeIds.forEach(tid => {
          const arr = change[tid] || [];
          if (arr[0]?.time_name === tName) itemsForTime.push(...arr);
        });

        // 7) Har bir parametrni o‘z ustuniga qo‘yish
        itemsForTime.forEach(item => {
          const pName = item.parameter_name;
          const idx = colIndexByParam.get(pName);
          if (idx === undefined) return;
          const col = currentStartCol + 1 + idx;

          const nVal = Number(item.Value);
          jexcel.setValueFromCoords(col, row, isNaN(nVal) ? item.Value : nVal);

          const minVal = Number(item.Min);
          const cellEl = jexcel.getCellFromCoords(col, row);

          // Min’dan kichik bo‘lsa — sariq/qizil
          if (cellEl && !isNaN(nVal) && !isNaN(minVal) && nVal < minVal) {
            cellEl.style.backgroundColor = "yellow";
            cellEl.style.color = "red";
            cellEl.style.fontWeight = "bold";
          }

          // Formula bilan bo‘lsa — hoshiyalash (ixtiyoriy)
          if (item.WithFormula === "1" && cellEl) {
            cellEl.style.border = "1px dashed #444";
          }
        });
      });

      setTimeout(() => setTableBorder(jexcel, currentStartRow, currentStartCol, rowCount, colCount, '#d10000'), 10);

      // 8) Keyingi jadval pozitsiyasi
      jadvallarInRow += 1;
      if (jadvallarInRow % 2 === 0) {
        currentStartRow += groupMaxRowCount + 2;
        currentStartCol = 0;
      } else {
        currentStartCol += colCount + 2;
      }
    });

    // Agar qator oxirida bitta jadval qolib ketsa — pastga tushiramiz
    if (jadvallarInRow % 2 !== 0) {
      currentStartRow += groupMaxRowCount + 2;
      currentStartCol = 0;
      jadvallarInRow = 0;
    }

    // 🔽 JADVAL TO‘LIQ CHIZILGANDAN KEYIN — KESHGA YOZAMIZ
    nextTick() // jexcel DOM/qiymatlar yakuniy bo‘lsin
    try {
      const page = Number(activeTab.value)
      if (page) {
        cacheWholeSheet(page, value.value) // ❗ kutamiz: tugamaguncha overlay yopilmaydi
      }
    } catch (err) {
      console.error('cacheWholeSheet error', err?.response?.data || err)
    }


  });
});






</script>
