<template>
    <div class="p-3 pt-28">
        <div class="flex items-center gap-3 mb-3">
            <VaButton preset="secondary" @click="$router.back()">← Orqaga</VaButton>
            <VaDateInput v-model="date" @update:modelValue="build" />
            <span class="text-gray-500">GMZ-3 bo‘yicha asosiy ko‘rsatkichlar</span>
        </div>
        <div ref="sheetEl" style="width:100vw;height:85vh;"></div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { VaButton, VaDateInput, useToast } from 'vuestic-ui'
import axios from 'axios'
import jspreadsheet from 'jspreadsheet-ce'
import 'jspreadsheet-ce/dist/jspreadsheet.css'
import 'jsuites/dist/jsuites.css'

const { init } = useToast()

const sheetEl = ref(null)
const date = ref(new Date())
let jexcel = null

// FORMULA SAHIFA
const numberPage = 303                 // joriy sahifa (GMZ-3)
const formulas = ref({})               // { 'C7': '=B4 + 304!B6*2', ... }
let applyingFormulas = false           // jexcel.setValue paytidagi onchange ni cheklash

// --- STATIC PARAMETRLAR
const staticItems = ref([])   // /static dan kelganlar (PName, UName, ...)
const apiDate = (d) => {
    const x = new Date(d)
    return `${String(x.getDate()).padStart(2, '0')}.${String(x.getMonth() + 1).padStart(2, '0')}.${x.getFullYear()}`
}
async function fetchStaticParams() {
    try {
        const { data } = await axios.get('/static')
        // Array yoki {items} bo‘lishi mumkin
        const arr = Array.isArray(data) ? data : (data.items || [])
        staticItems.value = arr
    } catch (e) {
        console.error(e)
        init({ message: 'Static parametrlarni yuklashda xatolik', color: 'danger' })
    }
}

// --- helpers
function dmy(d) {
    const dd = new Date(d)
    const day = String(dd.getDate()).padStart(2, '0')
    const mon = String(dd.getMonth() + 1).padStart(2, '0')
    const yr = dd.getFullYear()
    return `${day}.${mon}.${yr}`
}
function L(n) { let s = '', x = n; while (x >= 0) { s = String.fromCharCode((x % 26) + 65) + s; x = Math.floor(x / 26) - 1 } return s }
function cellToXY(cell) { const m = cell.match(/^([A-Z]+)(\d+)$/); const r = +m[2] - 1; let x = 0; for (const ch of m[1]) x = x * 26 + (ch.charCodeAt(0) - 64); return { x: x - 1, y: r } }
function merge(range) { const [a, b] = range.split(':'), A = cellToXY(a), B = cellToXY(b); return { row: Math.min(A.y, B.y), col: Math.min(A.x, B.x), rowspan: Math.abs(A.y - B.y) + 1, colspan: Math.abs(A.x - B.x) + 1 } }

// --- jadvalni chizish
async function build() {
    // /static dan kelgan nomlarni tayyorlab olamiz (dublikatlarni olib tashlaymiz)
    const rows = []
    const seen = new Set()
    for (const x of staticItems.value) {
        const pid = x?.ParameterID ?? x?.id ?? x?.Uuid
        if (seen.has(pid)) continue
        seen.add(pid)
        rows.push({
            name: x?.PName || x?.name || pid,                 // A ustun (Ko‘rsatgichlar nomi)
            unit: x?.UName || 'tn.'                           // B ustun (o‘lchov birligi, default 'tn.')
        })
    }

    const COLS = 150
    const ROWS = Math.max(150, 7 + rows.length + 2)
    const data = Array.from({ length: ROWS }, () => Array(COLS).fill(''))

    if (jexcel) { jexcel.destroy(); jexcel = null }

    jexcel = jspreadsheet(sheetEl.value, {
        data,
        columns: Array.from({ length: COLS }, (_, i) => ({
            title: L(i),
            width: i === 0 ? 520 : (i === 1 ? 120 : 110)
        })),
        minDimensions: [COLS, ROWS],
        allowInsertColumn: false,
        allowInsertRow: false,
        allowDeleteColumn: false,
        allowDeleteRow: false,
        textOverflow: true,
        merge: [
            merge('A1:F1'),           // sarlavha
            merge('A2:F2'),           // sana
            merge('B4:B5'),           // o'lchov birligi
            merge('C4:E4'),           // Kunlik
        ],

        // Foydalanuvchi Excel-uslubida formula kiritganda avtomatik saqlash
        onchange: async (instance, cell, x, y, value) => {
            if (applyingFormulas) return

            // x va y ni SON ga aylantiramiz
            const colIndex = Number(x)
            const rowIndex = Number(y) + 1
            const addr = `${L(colIndex)}${rowIndex}`

            if (typeof value === 'string' && value.trim().startsWith('=')) {
                try {
                    await saveAndApplyFormula(addr, value.trim())
                } catch (e) {
                    console.error(e)
                    init({ message: 'Formula xatolik', color: 'danger' })
                }
            }
        }

    })

    // headerlar
    jexcel.setValue('A1', 'GMZ-3 bo‘yicha asosiy ko‘rsatkichlar:')
    jexcel.setValue('A2', `${dmy(date.value)} y.`)
    jexcel.setValue('A3', '—')
    jexcel.setValue('A4', "Ko'rsatgichlar nomi.")
    jexcel.setValue('B4', "o'lchov birligi")
    jexcel.setValue('C4', 'Kunlik')
    jexcel.setValue('C5', 'reja')
    jexcel.setValue('D4', 'Kunlik')
    jexcel.setValue('D5', 'fakt/20:08')
    jexcel.setValue('E4', 'Kunlik')
    jexcel.setValue('E5', 'fakt/08:20')
    jexcel.setValue('F4', 'Kunlik')
    jexcel.setValue('F5', 'fakt jami')
    jexcel.setValue('G4', '')
    jexcel.setValue('G5', '%')

    jexcel.setValue('H4', 'Oylik')
    jexcel.setValue('H5', 'reja')
    jexcel.setValue('I4', 'Oylik')
    jexcel.setValue('I5', 'fakt')
    jexcel.setValue('J4', 'Kunlik')
    jexcel.setValue('J5', '%')

    jexcel.setStyle({
        A1: 'font-weight:bold;font-size:18px;',
        A2: 'font-weight:600;',
        A4: 'background:#f5f5c6;font-weight:bold;border:1px solid #bdbdbd;text-align:left;',
        B4: 'background:#e9ecef;font-weight:bold;border:1px solid #bdbdbd;text-align:center;',
        C4: 'background:#e6f4ff;font-weight:bold;border:1px solid #bdbdbd;text-align:center;',
        C5: 'background:#eef7ff;font-weight:bold;border:1px solid #bdbdbd;text-align:center;',
        D5: 'background:#eef7ff;font-weight:bold;border:1px solid #bdbdbd;text-align:center;',
        D4: 'background:#eef7ff;font-weight:bold;border:1px solid #bdbdbd;text-align:center;',
        E4: 'background:#eef7ff;font-weight:bold;border:1px solid #bdbdbd;text-align:center;',
        E5: 'background:#eef7ff;font-weight:bold;border:1px solid #bdbdbd;text-align:center;',
        F4: 'background:#eef7ff;font-weight:bold;border:1px solid #bdbdbd;text-align:center;',
        F5: 'background:#eef7ff;font-weight:bold;border:1px solid #bdbdbd;text-align:center;',
        G5: 'background:#eef7ff;font-weight:bold;border:1px solid #bdbdbd;text-align:center;',
        H4: 'background:#eef7ff;font-weight:bold;border:1px solid #bdbdbd;text-align:center;',
        H5: 'background:#eef7ff;font-weight:bold;border:1px solid #bdbdbd;text-align:center;',
        I4: 'background:#eef7ff;font-weight:bold;border:1px solid #bdbdbd;text-align:center;',
        I5: 'background:#eef7ff;font-weight:bold;border:1px solid #bdbdbd;text-align:center;',
        J4: 'background:#eef7ff;font-weight:bold;border:1px solid #bdbdbd;text-align:center;',
        J5: 'background:#eef7ff;font-weight:bold;border:1px solid #bdbdbd;text-align:center;',
    })
    const headerBorder = {}
        ;['A', 'B', 'C', 'D', 'E', 'F'].forEach(c => headerBorder[`${c}4`] = 'border:1px solid #bdbdbd;')
        ;['B', 'C', 'D', 'E'].forEach(c => headerBorder[`${c}5`] = 'border:1px solid #bdbdbd;')
    jexcel.setStyle(headerBorder)

    // --- QATORLARNI /static dan to‘ldirish
    let r = 7
    for (const it of rows) {
        jexcel.setValue(`A${r}`, it.name)
        jexcel.setValue(`B${r}`, it.unit)
        // jexcel.setValue(`C${r}`, '#ССЫЛКА!')
        // jexcel.setValue(`D${r}`, '#ССЫЛКА!')
        // jexcel.setValue(`E${r}`, '#ССЫЛКА!')

        const s = {}
        s[`A${r}`] = 'border:1px solid #dddddd;padding-left:6px;'
        s[`B${r}`] = 'border:1px solid #dddddd;text-align:center;'
            ;['C', 'D', 'E'].forEach(c => {
                s[`${c}${r}`] = 'border:1px solid #dddddd;text-align:center;color:#b60000;font-weight:600;'
            })
        jexcel.setStyle(s)
        jexcel.setHeight(r - 1, 26)
        r++
    }

    // sarlavha qator balandliklari
    jexcel.setHeight(0, 34)
    jexcel.setHeight(1, 28)
    jexcel.setHeight(3, 28)
    jexcel.setHeight(4, 24)

    // ✅ Saqlangan formulalarni yuklab, qo‘llaymiz
    await loadAndApplyAllFormulas()

    // ✅ (ixtiyoriy) namuna: aniq katakka dasturiy formula qo‘yish
    // await saveAndApplyFormula('C7', '=B4 + 304!B6*2')
}

// --- Cross-page qiymatlarni backend’dan olish
async function fetchRemoteCell(page, cellAddr, dateObj) {
    const { data } = await axios.get(`/sheet/value`, {
        params: { numberPage: page, cell: cellAddr, date: apiDate(dateObj) }
    })
    return Number(data?.value ?? 0)
}

// Excel ifodasidagi kross-sahifa qismini sonlarga almashtirib, qolganini
// jSpreadsheet ga formula sifatida beramiz (ichki C7, B4 larni u o‘zi hisoblaydi)
async function evaluateFormula(expr, dateObj) {
    let s = String(expr).trim()
    const reRef = /REF\(\s*(\d+)\s*,\s*"([A-Z]+\d+)"\s*\)/g     // REF(304,"B6")
    const reBang = /'?(\d+)'?\!([A-Z]+\d+)/g                      // 304!B6 yoki '304'!B6

    const refs = []
    s.replace(reRef, (_, p, c) => { refs.push({ page: +p, cell: c }); return '' })
    s.replace(reBang, (_, p, c) => { refs.push({ page: +p, cell: c }); return '' })

    const vals = await Promise.all(refs.map(r => fetchRemoteCell(r.page, r.cell, dateObj)))

    let i = 0
    s = s.replace(reRef, () => String(vals[i++]))
    s = s.replace(reBang, () => String(vals[i++]))
    if (!s.startsWith('=')) s = '=' + s
    return s
}

// Katakka formula qo‘yish + DBga saqlash
async function saveAndApplyFormula(cellAddr, expr) {
    // 1) local holat
    formulas.value[cellAddr] = expr

    // 2) DB ga saqlash
    await axios.post('/sheet/formula', {
        numberPage,
        cell: cellAddr,
        expr,                  // masalan: "=B4 + 304!B6 * 2"
        scope: 'permanent',    // ✅ doimiysi (for_date = NULL bo'ladi)
        // date yubormaymiz
    })

    // 3) kross-sahifa qismlarini raqamga aylantirib, katakka formula beramiz
    const compiled = await evaluateFormula(expr, date.value)
    applyingFormulas = true
    jexcel.setValue(cellAddr, compiled)   // masalan "=C7+B4+246"
    applyingFormulas = false

    const st = {}; st[cellAddr] = 'border:1px dashed #444;font-weight:600;'
    jexcel.setStyle(st)
}

// Sahifa ochilganda DB dagi formulalarni yuklab, qo‘llash
async function loadAndApplyAllFormulas() {
    const { data } = await axios.get('/sheet/formula', {
        params: { numberPage, date: apiDate(date.value) }
    })
        // kutilgan format: [{cell:'C7', expr:'=B4 + 304!B6*2'}, ...]
        ; (data?.items || data || []).forEach(f => { formulas.value[f.cell] = f.expr })
    for (const [cell, expr] of Object.entries(formulas.value)) {
        try {
            const compiled = await evaluateFormula(expr, date.value)
            applyingFormulas = true
            jexcel.setValue(cell, compiled)
            applyingFormulas = false
            const st = {}; st[cell] = 'border:1px dashed #444;font-weight:600;'
            jexcel.setStyle(st)
        } catch (e) { console.error('Formula error', cell, expr, e) }
    }
}

// yuklash va rebuild
onMounted(async () => {
    await fetchStaticParams()
    build()
})
watch(date, build)
watch(staticItems, build)
</script>
