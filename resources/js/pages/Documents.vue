<template>
  <div class="grid grid-rows-[55px,1fr]">
    <main></main>
    <main class="flex-grow mt-10">
      <VaDateInput v-model="value" class="mb-10" onchange="fetchData(value)" />
      <div ref="sheetEl" style="width:100vw;height:100vh;"></div>
    </main>
  </div>
</template>



<script setup>
import { ref, onMounted, watch } from 'vue'
import jspreadsheet from 'jspreadsheet-ce'
import 'jspreadsheet-ce/dist/jspreadsheet.css'
import 'jsuites/dist/jsuites.css'

const value = ref(new Date())
const sheetEl = ref(null)
const rowData = ref([]);

// VUE o'zgaruvchilar

let jexcel = null

const fetchData = async (date) => {
  try {
    const response = await axios.get(`/selectResultBlogs/${formatDate(date)}`)
    // Faqat API dan kelgan natijani BEVOSITA yozing!
    rowData.value = response.data // Faqat shu!
    console.log('API dan kelgan natija:', rowData.value)
  } catch (error) {
    console.error('Error fetching data:', error)
  }
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
    minDimensions: [300, 150],
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


//fetchData(value.value)
// Agar a1 yoki d1 o‘zgarsa, excelda ham o‘zgaradi
watch(value, (val) => {
  if (jexcel) {
    jexcel.setValue('A1', formatDate(val))
    fetchData(val)
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
watch(rowData, () => {
  const groupIds = Object.keys(rowData.value || {});
  if (!jexcel || !groupIds.length) return;

  let currentStartRow = 2;
  let currentStartCol = 0;
  let maxColInRow = 0; // Har bir qatordagi eng ko‘p ustun soni (eng keng jadval uchun)
  let jadvallarInRow = 0; // Qator (yani row) ichida nechta jadval yonma-yon chiqadi

  groupIds.forEach((groupId, groupIdx) => {
    const group = rowData.value[groupId];
    const changeIds = Object.keys(group);
    let groupMaxRowCount = 0; // 🟩 Shu group ichida maksimal rowCount saqlanadi

    changeIds.forEach((changeId, changeIdx) => {
      const change = group[changeId];
      const timeIds = Object.keys(change);
      const paramsSample = change[timeIds[0]] || [];
      if (!paramsSample.length) return;
      const parameterNames = paramsSample.map(x => x.parameter_name);
      const timeNames = timeIds.map(tid => change[tid][0]?.time_name);

      const colCount = parameterNames.length + 1;
      const rowCount = timeNames.length + 3;

      // 🟩 Max rowCount yangilab boriladi
      if (rowCount > groupMaxRowCount) groupMaxRowCount = rowCount;

      jexcel.setValueFromCoords(currentStartCol, currentStartRow, paramsSample[0]?.group_name || '');
      jexcel.setValueFromCoords(currentStartCol, currentStartRow + 1, changeId + '-Smena');


      // group rang
      let groupStyle = {};
      for (let i = 0; i < colCount; i++) {
        const cell = `${getColLetter(currentStartCol + i)}${currentStartRow + 1}`;
        groupStyle[cell] = 'background: #c7efc4; font-weight: bold;';
      }
      setTimeout(() => jexcel.setStyle(groupStyle), 20);
      // smena rang
      let shiftStyle = {};
      for (let i = 0; i < colCount; i++) {
        const cell = `${getColLetter(currentStartCol + i)}${currentStartRow + 2}`;
        shiftStyle[cell] = 'background: #cfe2f3; font-weight: bold;';
      }
      setTimeout(() => jexcel.setStyle(shiftStyle), 20);

      let paramStyle = {};
      for (let i = 0; i < colCount; i++) {
        const cell = `${getColLetter(currentStartCol + i)}${currentStartRow + 3}`;
        paramStyle[cell] = 'background: #c9b2d6; font-weight: bold;';
      }
      setTimeout(() => jexcel.setStyle(paramStyle), 20);

      jexcel.setValueFromCoords(currentStartCol, currentStartRow + 2, "Vaqt");
      parameterNames.forEach((paramName, colIdx) => {
        jexcel.setValueFromCoords(currentStartCol + 1 + colIdx, currentStartRow + 2, paramName);
      });

      timeNames.forEach((timeName, rowIdx) => {
        const row = currentStartRow + 3 + rowIdx;

        // Vaqtni birinchi ustunga yozish
        jexcel.setValueFromCoords(currentStartCol, row, formatShortTime(timeName));
        const vaqtCell = jexcel.getCellFromCoords(currentStartCol, row);
        if (vaqtCell) {
          vaqtCell.style.backgroundColor = "#bbfcc1";
          vaqtCell.style.fontWeight = "bold";
        }
        const rowParams = change[timeIds[rowIdx]] || [];

        // 🔁 Endi rowParams dan barcha parameter_name larni dinamik olamiz
        rowParams.forEach((paramObj, colIdx) => {
          const paramName = paramObj.parameter_name;
          const col = currentStartCol + 1 + colIdx;

          const cellVal = Number(paramObj.Value);
          const minVal = Number(paramObj.Min);

          // Qiymatni yozish
          jexcel.setValueFromCoords(col, row, cellVal);

          // DOM cell ni olish
          const cellEl = jexcel.getCellFromCoords(col, row);

          // Ranglash sharti
          if (cellEl && !isNaN(cellVal) && !isNaN(minVal) && cellVal < minVal) {
            cellEl.style.backgroundColor = "yellow";
            cellEl.style.color = "red";
            cellEl.style.fontWeight = "bold";
            console.log(`✅ ${paramName} (${col}, ${row}) = ${cellVal} < ${minVal}`);
          }
        });
      });

      setTimeout(() => setTableBorder(jexcel, currentStartRow, currentStartCol, rowCount, colCount, '#d10000'), 10);

      maxColInRow = Math.max(maxColInRow, colCount);
      jadvallarInRow += 1;
      if (jadvallarInRow % 2 === 0) {
        currentStartRow += groupMaxRowCount + 2; // 🟩 Pastga o‘tish max rowCount asosida
        currentStartCol = 0;
        maxColInRow = 0;
      } else {
        currentStartCol += colCount + 2;
      }
    });

    if (jadvallarInRow % 2 !== 0) {
      currentStartRow += groupMaxRowCount + 2; // 🟩 groupMaxRowCount ishlatiladi
      currentStartCol = 0;
      maxColInRow = 0;
      jadvallarInRow = 0;
    }
  });

});





</script>
