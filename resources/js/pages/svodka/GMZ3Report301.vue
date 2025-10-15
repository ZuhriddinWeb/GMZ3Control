<template>
    <div class="p-3 pt-28">
        <div class="flex items-center gap-3 mb-3">
            <div class="flex items-center gap-2 mb-2">
                <VaInput v-model="formulaBar" class="max-w-[700px] w-full" placeholder=""
                    @keyup.enter="applyFormulaBar" />
                <VaButton size="small" @click="applyFormulaBar" :disabled="!selectedCell">
                    Formula’ni saqla
                </VaButton>
                <span v-if="selectedCell" class="text-gray-500 text-sm">Katak: {{ selectedCell }}</span>
            </div>
            <VaButton preset="secondary" @click="$router.back()">← Orqaga</VaButton>
            <VaDateInput v-model="date" @update:modelValue="build" :teleport="true" teleport-target="body"
                placement="bottom-start" :offset="[0, 8]" />

            <span class="text-gray-500">GMZ-3 bo‘yicha asosiy ko‘rsatkichlar</span>
        </div>
        <div ref="sheetEl" style="width:100vw;height:85vh;"></div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { VaButton, VaDateInput, VaInput, useToast } from 'vuestic-ui' // ⬅️ VaInput qo'shildi
import axios from 'axios'
import jspreadsheet from 'jspreadsheet-ce'
import 'jspreadsheet-ce/dist/jspreadsheet.css'
import 'jsuites/dist/jsuites.css'

const { init } = useToast()

const sheetEl = ref(null)
const date = ref(new Date())
let jexcel = null
const periodTypes = ref([])
const paramIdToRow = ref(new Map())
const paramNameById = ref(new Map())
const paramIdByName = ref(new Map())
const periodCols = ref(new Map())       // colIndex (0-based) -> { id, label }
const colByPeriodId = ref(new Map())    // period_id -> colIndex (0-based)
// FORMULA SAHIFA
const numberPage = 303                 // joriy sahifa (GMZ-3)
const formulas = ref({})               // { 'C7': '=B4 + 304!B6*2', ... }
let applyingFormulas = false           // jexcel.setValue paytidagi onchange ni cheklash
// yuqoriga qo‘shing:
const paramRowMap = ref(new Map()) // rowIndex (1-based) -> { fsId, groupId, parameterId }
let isBuilding = false
const selectedCell = ref(null)   // masalan: "C9"
const formulaBar = ref('')     // asl formula (REF/304! …)
// --- STATIC PARAMETRLAR
const staticItems = ref([])   // /static dan kelganlar (PName, UName, ...)
const apiDate = (d) => {
    const x = new Date(d)
    return `${String(x.getDate()).padStart(2, '0')}.${String(x.getMonth() + 1).padStart(2, '0')}.${x.getFullYear()}`
}
function rebuildParamNameIndex() {
    const byId = new Map(), byName = new Map()
    for (const x of (staticItems.value || [])) {
        const id = String(x.ParameterID || x.parameter_id || x.id || '').toUpperCase()
        const name = String(x.PName || x.Name || '').trim()
        if (!id || !name) continue
        byId.set(id, name)
        byName.set(name.toLowerCase(), id)
    }
    paramNameById.value = byId
    paramIdByName.value = byName
}
// DBdan kelgan stabil ifodani foydalanuvchiga ko'rsatish uchun (GUID -> nom)
function toDisplayFormula(exprStable) {
    return String(exprStable || '').replace(
        /P\(\s*([0-9A-F-]{8,})\s*(,\s*"(.*?)")?\s*\)/gi,
        (_, id, _2, slot) => {
            const name = paramNameById.value.get(String(id).toUpperCase()) || id
            return `P("${name}"${slot ? `,"${slot}"` : ''})`
        }
    )
}

// Foydalanuvchi P("Nom","slot") yozsa – GUIDga aylantirib olamiz
function namesToIdsInFormula(expr) {
    return String(expr || '').replace(
        /P\(\s*"(.*?)"\s*(,\s*"(.*?)")?\s*\)/gi,
        (_, name, _2, slot) => {
            const id = paramIdByName.value.get(String(name).trim().toLowerCase())
            return id ? `P(${id}${slot ? `,"${slot}"` : ''})` : `P("${name}"${slot ? `,"${slot}"` : ''})`
        }
    )
}

watch(staticItems, rebuildParamNameIndex, { immediate: true })
async function applyFormulaBar() {
    const addr = selectedCell.value
    const exprUi = (formulaBar.value || '').trim()
    if (!addr || !exprUi.startsWith('=')) {
        init({ message: 'Formula "=" bilan boshlanishi kerak', color: 'warning' })
        return
    }
    try {
        const exprWithIds = namesToIdsInFormula(exprUi)  // << nomdan GUIDga
        await saveAndApplyFormula(addr, exprWithIds)
        await new Promise(r => requestAnimationFrame(r))
        await postStaticForCell(addr, { toast: true })
        init({ message: 'Formula yangilandi', color: 'success' })
    } catch (e) {
        console.error(e)
        init({ message: 'Formula saqlanmadi', color: 'danger' })
    }
}


async function fetchStaticParams() {
    try {
        const { data } = await axios.get(`/static-with-numberpage/${numberPage}`, {
            params: { date: ymd(date.value) }   // <-- qo'shildi
        })
        staticItems.value = Array.isArray(data) ? data : (data.items || [])
    } catch (e) {
        console.error(e)
        init({ message: 'Static parametrlarni yuklashda xatolik', color: 'danger' })
    }
}

function rebuildParamReverseIndex() {
    const m = new Map()
    for (const [rowIndex, meta] of paramRowMap.value.entries()) {
        if (meta?.parameterId != null) m.set(String(meta.parameterId), Number(rowIndex))
    }
    paramIdToRow.value = m
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
async function fetchPeriodTypes() {
    try {
        const { data } = await axios.get('/periodType')
        periodTypes.value = Array.isArray(data) ? data : (data.items || [])
    } catch (e) {
        console.error(e)
        init({ message: 'Period turlarini yuklashda xatolik', color: 'danger' })
    }
}
// Bazadan kelgan periodlarni OrderNumber bo‘yicha tartiblaydi,
// nomi bir xil bo'lganlarini bitta qiladi.

// Berilgan period label uchun [start,end] (YYYY-MM-DD) qaytaradi
function periodRangeFor(label, baseDate) {
    const d = new Date(baseDate)
    const y = d.getFullYear()
    const m = d.getMonth()
    const dd = d.getDate()

    const ymd = (x) =>
        `${x.getFullYear()}-${String(x.getMonth() + 1).padStart(2, '0')}-${String(x.getDate()).padStart(2, '0')}`

    const low = label.trim().toLowerCase()

    if (low === 'kun') {
        const s = new Date(y, m, dd)
        return { start: ymd(s), end: ymd(s) }
    }

    if (low === 'hafta') {
        // ISO: hafta dushanbadan boshlanadi
        const day = (d.getDay() + 6) % 7 // 0..6 (0 = Mon)
        const s = new Date(y, m, dd - day)
        const e = new Date(s); e.setDate(s.getDate() + 6)
        return { start: ymd(s), end: ymd(e) }
    }

    if (low === 'oy') {
        const s = new Date(y, m, 1)
        const e = new Date(y, m + 1, 0)
        return { start: ymd(s), end: ymd(e) }
    }

    if (low === 'kvartal') {
        const q = Math.floor(m / 3) // 0..3
        const s = new Date(y, q * 3, 1)
        const e = new Date(y, q * 3 + 3, 0)
        return { start: ymd(s), end: ymd(e) }
    }

    if (low === 'yil') {
        const s = new Date(y, 0, 1)
        const e = new Date(y, 12, 0)
        return { start: ymd(s), end: ymd(e) }
    }

    // default: 1 kunlik deb olamiz
    const s = new Date(y, m, dd)
    return { start: ymd(s), end: ymd(s) }
}

const toNum = (v, fallback = Number.POSITIVE_INFINITY) => {
    const n = Number(v)
    return Number.isFinite(n) ? n : fallback
}
// Bazadan kelgan periodlarni OrderNumber bo‘yicha tartiblab,
// nomi bir xil bo'lganlarini (case-insensitive) bitta qilib qaytaradi.
function getOrderedUniquePeriodTypes() {
    const raw = Array.isArray(periodTypes.value) ? periodTypes.value : []
    const rows = raw.map(pt => ({
        id: Number(pt.id),
        name: String(pt.name || '').trim(),
        order: Number(
            pt.OrderNumber ?? pt.orderNumber ?? pt.order_number ?? pt.order ?? 9999
        ),
    }))

    // OrderNumber ↑, keyin id ↑
    rows.sort((a, b) => (a.order - b.order) || (a.id - b.id))

    // nom bo‘yicha dublikatlarni olib tashlaymiz
    const seen = new Set()
    const unique = []
    for (const r of rows) {
        const key = r.name.toLowerCase()
        if (!key || seen.has(key)) continue
        seen.add(key)
        unique.push({ id: r.id, label: r.name })
    }
    return unique
}
// Avvalgi 'daily'/'monthly' o'rniga colIndex -> period_id qaytaramiz
function colToPeriodType(colIndex) {
    const meta = periodCols.value.get(Number(colIndex))
    return meta ? meta.id : null
}

// --- jadvalni chizish
async function build() {
    isBuilding = true
    paramRowMap.value.clear()
    paramRowMap.value = new Map()
    // 1) FS->Group->Param tuzilma
    const fsList = groupStaticByFSAndGroup(staticItems.value)

    // 2) Kerakli qatorlar sonini hisoblash (FS sarlavha + group sarlavha + param + bo'sh qatorlar)
    const paramCount = fsList.reduce((a, fs) => a + fs.groups.reduce((b, g) => b + g.params.length, 0), 0)
    const groupHeaderCount = fsList.reduce((a, fs) => a + fs.groups.filter(g => !!g.gName).length, 0)
    const fsHeaderAndBlank = fsList.length * 2   // har FS: 1 header + 1 bo'sh qator
    const needRows = 7 + paramCount + groupHeaderCount + fsHeaderAndBlank + 5

    const COLS = 150
    const ROWS = Math.max(needRows, 300)
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
            merge('A1:F1'),
            merge('A2:F2'),
            merge('B4:B5'),
            // merge('C4:E4'),
        ],
        onselection: (instance, x1, y1, x2, y2) => {
            if (x1 !== x2 || y1 !== y2) return
            const addr = `${L(x1)}${y1 + 1}`
            selectedCell.value = addr
            const stable = formulas.value[addr] || ''
            formulaBar.value = toDisplayFormula(stable)   // << GUID emas, nom ko'rinadi
        },


        onchange: async (instance, cell, x, y, value) => {
            if (applyingFormulas || isBuilding) return

            const rowIndex = Number(y) + 1
            const colIndex = Number(x)
            const addr = `${L(colIndex)}${rowIndex}`

            const meta = paramRowMap.value.get(rowIndex)
            if (!meta) return

            const periodKind = colToPeriodType(colIndex)
            if (!periodKind) return

            try {
                if (typeof value === 'string' && value.trim().startsWith('=')) {
                    await saveAndApplyFormula(addr, value.trim())
                    await new Promise(r => requestAnimationFrame(r))
                    await postStaticForCell(addr, { toast: true })

                    // 🔽 tanlangan katak shu bo‘lsa, formula panelini ham xuddi shu xom ifoda bilan yangilaymiz
                    if (selectedCell.value === addr) {
                        formulaBar.value = value.trim()
                    }
                } else {
                    await postStaticForCell(addr, { toast: true })
                }

            } catch (e) {
                console.error(e)
                init({ message: 'Saqlashda xatolik', color: 'danger' })
            }
        }


    })


    // headerlar (o'zgarmagan)
    jexcel.setValue('A1', 'GMZ-3 bo‘yicha asosiy ko‘rsatkichlar:')
    jexcel.setValue('A2', `${dmy(date.value)} y.`)
    jexcel.setValue('A3', '—')
    jexcel.setValue('A4', "Ko'rsatgichlar nomi.")
    jexcel.setValue('B4', "o'lchov birligi")
    // const { dailyLabel, monthlyLabel } = resolvePeriodLabels()
    // console.log(dailyLabel, monthlyLabel);
    const periods = getOrderedUniquePeriodTypes()
    periodCols.value.clear()
    colByPeriodId.value.clear()

    let col = 2 // C
    const row4 = {}
    const row5 = {}

    for (const p of periods) {
        const cL = L(col)
        jexcel.setValue(`${cL}4`, p.label)
        jexcel.setValue(`${cL}5`, '') // pastdagi "reja/fakt" lar bo'lmasin

        row4[`${cL}4`] = 'background:#e6f4ff;font-weight:bold;border:1px solid #bdbdbd;text-align:center;'
        row5[`${cL}5`] = 'background:#fff;border:0;text-align:center;'

        periodCols.value.set(col, { id: p.id, label: p.label })
        colByPeriodId.value.set(p.id, col)

        col += 1
    }

    jexcel.setStyle({
        A1: 'font-weight:bold;font-size:18px;',
        A2: 'font-weight:600;',
        A4: 'background:#f5f5c6;font-weight:bold;border:1px solid #bdbdbd;text-align:left;',
        B4: 'background:#e9ecef;font-weight:bold;border:1px solid #bdbdbd;text-align:center;',
    })
    jexcel.setStyle(row4)
    jexcel.setStyle(row5)
    const headerBorder = {}
        ;['A', 'B', 'C', 'D', 'E', 'F'].forEach(c => headerBorder[`${c}4`] = 'border:1px solid #bdbdbd;')
        ;['B', 'C', 'D', 'E'].forEach(c => headerBorder[`${c}5`] = 'border:1px solid #bdbdbd;')
    jexcel.setStyle(headerBorder)

    // === YANGI: FS -> Group -> Parametrlarni CHIZISH ===
    function spanRowAcrossAtoJ(rowIdx, styleStr) {
        try {
            if (typeof jexcel.setMerge === 'function') {
                jexcel.setMerge(`A${rowIdx}`, 10, 1) // A..J — 10 ustun
            }
        } catch (e) { }
        const s = {}
        for (let c = 0; c <= 9; c++) s[`${L(c)}${rowIdx}`] = styleStr
        jexcel.setStyle(s)
    }

    let r = 7

    for (const fs of fsList) {
        // FS header
        jexcel.setValue(`A${r}`, fs.fsName)
        spanRowAcrossAtoJ(r, 'background:#f1f5f9;font-weight:700;border:1px solid #bdbdbd;text-align:left;padding-left:6px;')
        jexcel.setHeight(r - 1, 28)
        r++

        for (const g of fs.groups) {
            if (g.gName) {
                jexcel.setValue(`A${r}`, g.gName)
                // ⬇⬇⬇ merge emas!
                paintRowAcrossAtoJ(
                    r,
                    'background:#fff7e6;font-weight:600;border:1px solid #e0e0e0;text-align:left;padding-left:8px;'
                )
                jexcel.setHeight(r - 1, 26)
                r++
            }

            for (const p of g.params) {
                paramRowMap.value.set(r, {
                    fsId: p.fsId,
                    groupId: p.groupId,
                    parameterId: p.parameterId,
                })
                paramRowMap.value.set(r, {
                    fsId: p.fsId, groupId: p.groupId, parameterId: p.parameterId,
                })

                jexcel.setValue(`A${r}`, p.name)
                jexcel.setValue(`B${r}`, p.unit)

                const s = {}
                s[`A${r}`] = 'border:1px solid #dddddd;padding-left:6px;'
                s[`B${r}`] = 'border:1px solid #dddddd;text-align:center;'
                    ;['C', 'D', 'E'].forEach(c => {
                        s[`${c}${r}`] = 'border:1px solid #dddddd;text-align:center;color:#b60000;font-weight:600;'
                    })
                jexcel.setStyle(s)
                jexcel.setHeight(r - 1, 24)
                r++
            }
            rebuildParamReverseIndex()
        }

        // FS blokidan keyin bitta bo'sh qator
        r++

    }

    // sarlavha qator balandliklari
    jexcel.setHeight(0, 34)
    jexcel.setHeight(1, 28)
    jexcel.setHeight(3, 28)
    jexcel.setHeight(4, 24)
    await fillExistingStaticValues()
    await loadAndApplyAllFormulas()

    await new Promise(r => requestAnimationFrame(r))

    // Faqat 4–5-qatorni pin qilmoqchi bo‘lsangiz:
    pinRowsSticky([1, 2, 4, 5])
    // Formulalarni qo‘llash
    isBuilding = false
}


// --- Cross-page qiymatlarni backend’dan olish
async function fetchRemoteCell(page, cellAddr, dateObj) {
    const { data } = await axios.get(`/sheet/value`, {
        params: { numberPage: page, cell: cellAddr, date: apiDate(dateObj) }
    })
    return Number(data?.value ?? 0)
}

function resolvePeriodIds() {
    const map = new Map((periodTypes.value || []).map(pt => [String(pt.id), String(pt.name || '').trim().toLowerCase()]))
    const used = [...new Set((staticItems.value || []).map(x => x?.period_type_id).filter(v => v != null).map(String))]
    const findId = (preds) => used.find(id => preds.some(p => (map.get(id) || '').includes(p)))
    let dailyId = findId(['kun', 'sutk']) || (used.includes('1') ? '1' : null)
    let monthlyId = findId(['oy', 'mes']) || (used.includes('2') ? '2' : null)
    // fallback
    if (!dailyId && used.length) dailyId = used[0]
    if (!monthlyId && used.length) monthlyId = used.find(id => id !== dailyId) || used[0]
    return { dailyId: Number(dailyId), monthlyId: Number(monthlyId) }
}
function ymd(d) {
    const x = new Date(d)
    return `${x.getFullYear()}-${String(x.getMonth() + 1).padStart(2, '0')}-${String(x.getDate()).padStart(2, '0')}`
}

function monthRange(d) {

    const x = new Date(d)
    const start = new Date(x.getFullYear(), x.getMonth(), 1)
    const end = new Date(x.getFullYear(), x.getMonth() + 1, 0)
    return { start: ymd(start), end: ymd(end) }
}

// Harfni indexga
function colLetterToIndex(colLtr) {
    let x = 0
    for (const ch of colLtr) x = x * 26 + (ch.charCodeAt(0) - 64)
    return x - 1
}

// Normalizatsiya (faqat harf/raqamni qoldiramiz)
function norm(s) {
    return String(s || '')
        .toLowerCase()
        .replace(/[^\p{L}\p{N}]+/gu, '') // harf/raqamdan boshqasini olib tashlash
        .trim()
}

// Matndan period_id topish (faqat DB ro'yxati asosida)
function argToPeriodId(arg, defaultId = null) {
    if (arg == null || String(arg).trim() === '') return defaultId

    const raw = String(arg).trim()

    // Masalan "daily_plan" -> "daily", "oy_plan" -> "oy"
    const head = raw.split('_')[0]

    // Raqam bo'lsa - id
    if (/^\d+$/.test(head)) return Number(head)

    const wanted = norm(head)
    const list = Array.isArray(periodTypes.value) ? periodTypes.value : []

    const hit = list.find(pt => norm(pt.name) === wanted)
    return hit ? Number(hit.id) : defaultId
}

async function fillExistingStaticValues() {
    const rows = staticItems.value || []
    if (!rows.length) return

    // onchange triggersiz
    const prev = applyingFormulas
    applyingFormulas = true

    // Sana oralig'i — joriy sanaga nisbatan period bo'yicha
    // (har bir period uchun start/end ni hisoblaymiz)
    const periodRanges = new Map()
    for (const [colIndex, meta] of periodCols.value.entries()) {
        periodRanges.set(meta.id, periodRangeFor(meta.label, date.value))
    }

    // paramId -> rowIndex
    const paramToRow = new Map()
    for (const [rowIndex, meta] of paramRowMap.value.entries()) {
        if (meta?.parameterId) paramToRow.set(String(meta.parameterId), rowIndex)
    }

    for (const x of rows) {
        const pid = String(x.ParameterID || x.parameter_id || '')
        const rowIndex = paramToRow.get(pid)
        if (!rowIndex) continue

        const typeId = Number(x.period_type_id)
        const tgtColIndex = colByPeriodId.value.get(typeId)
        if (tgtColIndex == null) continue

        const expected = periodRanges.get(typeId)
        const start = String(x.period_start_date || '')
        const end = String(x.period_end_date || start || '')

        // Shu oyning/haftaning/yilning mos oralig'idek bo'lsa joylaymiz
        if (expected && start === expected.start && end === expected.end) {
            const cL = L(tgtColIndex)
            jexcel.setValue(`${cL}${rowIndex}`, x.value)
        }
    }

    applyingFormulas = prev
}


// FS -> Group -> Parametrlar ko‘rinishiga keltirish
function groupStaticByFSAndGroup(items) {
    const toNum = (v) => {
        const s = String(v ?? '').trim().toLowerCase()
        if (s === '' || s === 'null' || s === 'undefined') return null
        const n = Number(s)
        return Number.isFinite(n) ? n : null
    }


    let seq = 0
    const fsMap = new Map()
    const seen = new Set()

    for (const x of (items || [])) {
        const seenAt = seq++

        // --- SEX
        const fsId = x?.FactoryStructureID ?? null
        const fsName = x?.FSName || (fsId != null ? `Struktura #${fsId}` : 'Struktura')
        const fsOrder = toNum(x?.OrderNumberSex)

        // --- GROUP (nomi bo‘lmasa guruhsizga tushiramiz)
        let gId = x?.GroupID ?? null
        const gNameRaw = x?.GName
        const gHasName = !!String(gNameRaw ?? '').trim()
        if (gId != null && !gHasName) gId = null
        const gName = gHasName ? gNameRaw : null

        const gOrder = toNum(x?.OrderNumberGroup)

        // --- PARAM
        const pid = x?.ParameterID ?? x?.id ?? x?.Uuid
        if (pid && seen.has(`${fsId}:${gId}:${pid}`)) continue
        if (pid) seen.add(`${fsId}:${gId}:${pid}`)

        const pName = x?.PName || x?.Name || `Param ${pid}`
        const unit = x?.UName || ''
        const pOrder = toNum(x?.OrderNumber)

        // FS init
        if (!fsMap.has(fsId)) {
            fsMap.set(fsId, { fsId, fsName, fsOrder, seq: seenAt, groups: new Map() })
        }
        const fs = fsMap.get(fsId)
        if (fs.fsOrder == null && fsOrder != null) fs.fsOrder = fsOrder
        fs.seq = Math.min(fs.seq, seenAt)

        // Group init
        const gKey = (gId == null) ? 'zzz_nogroup' : `g_${gId}`
        if (!fs.groups.has(gKey)) {
            fs.groups.set(gKey, { groupId: gId, gName, gOrder, seq: seenAt, params: [] })
        }
        const g = fs.groups.get(gKey)
        if (g.gOrder == null && gOrder != null) g.gOrder = gOrder
        g.seq = Math.min(g.seq, seenAt)

        g.params.push({ name: pName, unit, fsId, groupId: gId, parameterId: pid, pOrder, seq: seenAt })
    }

    // --- SEX: order bor (asc) -> order yo'q (kelgan tartib)
    let fsList = Array.from(fsMap.values())
    const fsWithOrder = fsList.filter(f => f.fsOrder != null).sort((a, b) => a.fsOrder - b.fsOrder || a.seq - b.seq)
    const fsNoOrder = fsList.filter(f => f.fsOrder == null).sort((a, b) => a.seq - b.seq)
    fsList = [...fsWithOrder, ...fsNoOrder]

    // --- GROUP & PARAM ichma-ich sort
    fsList.forEach(fs => {
        const groups = Array.from(fs.groups.values())

        const withOrder = groups.filter(g => g.groupId != null && g.gOrder != null).sort((a, b) => a.gOrder - b.gOrder || a.seq - b.seq)
        const noOrder = groups.filter(g => g.groupId != null && g.gOrder == null).sort((a, b) => a.seq - b.seq)
        const noGroup = groups.filter(g => g.groupId == null).sort((a, b) => a.seq - b.seq) // doim oxirida

        fs.groups = [...withOrder, ...noOrder, ...noGroup]

        fs.groups.forEach(g => {
            const pWith = g.params.filter(p => p.pOrder != null).sort((a, b) => a.pOrder - b.pOrder || a.seq - b.seq)
            const pNo = g.params.filter(p => p.pOrder == null).sort((a, b) => a.seq - b.seq)
            g.params = [...pWith, ...pNo]
        })
    })

    return fsList
}
function paintRowAcrossAtoJ(rowIdx, styleStr) {
    // B..J ni tozalab, A..J ga style beramiz
    for (let c = 1; c <= 9; c++) jexcel.setValueFromCoords(c, rowIdx - 1, '')
    const st = {}
    for (let c = 0; c <= 9; c++) st[`${L(c)}${rowIdx}`] = styleStr
    jexcel.setStyle(st)
}




async function evaluateFormula(expr, dateObj, { currentCell } = {}) {
    let s = String(expr).trim()

    // 🔹 joriy katak ustunidagi period id (default sifatida)
    let defaultPeriodId = null
    if (currentCell) {
        const m = currentCell.match(/^([A-Z]+)\d+$/)
        if (m) {
            const colIndex = colLetterToIndex(m[1])
            const meta = periodCols.value.get(colIndex) // { id, label }
            if (meta) defaultPeriodId = meta.id
        }
    }

    // 🔹 P(<param_id>[,"<name|id|alias>"]) -> <CellRef>
    s = s.replace(
        /P\(\s*([0-9A-F-]{8,})\s*(?:,\s*"(.*?)")?\s*\)/gi,
        (_, pid, argRaw) => {
            const row = paramIdToRow.value.get(String(pid).toUpperCase())
            const periodId = argToPeriodId(argRaw, defaultPeriodId)
            const colIndex = colByPeriodId.value.get(periodId)
            const colLtr = (colIndex != null) ? L(colIndex) : null
            return (row && colLtr) ? `${colLtr}${row}` : '0'
        }
    )

    // ... (REF/304!B6 qismlari o'zgarishsiz)
    const reRef = /REF\(\s*(\d+)\s*,\s*"([A-Z]+\d+)"\s*\)/g
    const reBang = /'?(\d+)'?\!([A-Z]+\d+)/g
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


function toStableFormula(expr) {
    return String(expr || '').replace(/\b([A-Z]+)(\d+)\b/g, (m, colLtr, rowStr) => {
        const row = Number(rowStr)
        const meta = paramRowMap.value.get(row)
        if (!meta?.parameterId) return m

        const colIndex = colLetterToIndex(colLtr)
        const pmeta = periodCols.value.get(colIndex) // { id, label }
        if (!pmeta) return m

        // Stabil format: P(<param_uuid>,"<period label from DB>")
        return `P(${meta.parameterId},"${pmeta.label}")`
    })
}


function colToSlot(colLtr) {
    switch (colLtr) {
        case 'C': return 'daily_plan'
        case 'D': return 'daily_fact_20_08'
        case 'E': return 'daily_fact_08_20'
        case 'F': return 'daily_fact_total'
        case 'G': return 'daily_percent'
        case 'H': return 'monthly_plan'
        case 'I': return 'monthly_fact'
        case 'J': return 'monthly_percent'
        default: return null
    }
}
function slotToCol(slot) {
    const map = {
        daily_plan: 'C', daily_fact_20_08: 'D', daily_fact_08_20: 'E',
        daily_fact_total: 'F', daily_percent: 'G',
        monthly_plan: 'H', monthly_fact: 'I', monthly_percent: 'J'
    }
    return map[slot] || null
}

// Excel ifodasidagi kross-sahifa qismini sonlarga almashtirib, qolganini
// jSpreadsheet ga formula sifatida beramiz (ichki C7, B4 larni u o‘zi hisoblaydi)
async function saveAndApplyFormula(cellAddr, exprRaw) {
    const rowIdx = Number(cellAddr.match(/\d+/)?.[0] || 0)
    const colLtr = String(cellAddr.match(/^[A-Z]+/)?.[0] || '')
    const meta = paramRowMap.value.get(rowIdx)
    if (!meta?.parameterId) return

    const colIndex = colLetterToIndex(colLtr)
    const pmeta = periodCols.value.get(colIndex) // { id, label }
    if (!pmeta) return

    const exprStable = toStableFormula(exprRaw)
    formulas.value[cellAddr] = exprStable

    await axios.post('/sheet/formula', {
        numberPage,
        number_page: numberPage,

        // 🔹 asosiy kalit: parametr + period_type_id (+ for_date)
        param_id: meta.parameterId,
        period_type_id: pmeta.id,
        period_label: pmeta.label, // ixtiyoriy, backendda moslash uchun

        // legacy diagnostika uchun qoldirishingiz mumkin:
        cell: cellAddr,

        expr: exprStable,
        expr_stable: exprStable,
        expr_raw: exprRaw,
        scope: 'permanent',      // yoki 'dated' bo'lsa for_date ishlatiladi
        date: ymd(date.value),
        for_date: null,
    })

    const compiled = await evaluateFormula(exprStable, date.value, { currentCell: cellAddr })
    applyingFormulas = true
    jexcel.setValue(cellAddr, compiled)
    applyingFormulas = false
    await new Promise(r => requestAnimationFrame(r))
    jexcel.setStyle({ [cellAddr]: 'border:1px dashed #444;font-weight:600;' })
}




// Formulalarni yuklash paytida ham static’ga yozib qo‘yish:
async function loadAndApplyAllFormulas() {
    const { data } = await axios.get('/sheet/formula', {
        params: { numberPage, date: apiDate(date.value) }
    })
    const items = (data?.items || data || [])

    for (const f of items) {
        // ✅ Yangi backend: param_id + period_type_id
        let rowIndex = null, colIndex = null

        if (f.param_id && (f.period_type_id != null)) {
            rowIndex = paramIdToRow.value.get(String(f.param_id))
            colIndex = colByPeriodId.value.get(Number(f.period_type_id))
        }

        // 🔁 Legacy fallback (cell bo'yicha)
        if ((rowIndex == null || colIndex == null) && f.cell) {
            const m = String(f.cell).match(/^([A-Z]+)(\d+)$/)
            if (m) { colIndex = colLetterToIndex(m[1]); rowIndex = Number(m[2]) }
        }

        if (!rowIndex || colIndex == null) continue
        const addr = `${L(colIndex)}${rowIndex}`

        const exprStable = f.expr_stable || f.expr || ''
        formulas.value[addr] = exprStable

        try {
            const compiled = await evaluateFormula(exprStable, date.value, { currentCell: addr })
            applyingFormulas = true
            jexcel.setValue(addr, compiled)
            applyingFormulas = false
            jexcel.setStyle({ [addr]: 'border:1px dashed #444;font-weight:600;' })
            await postStaticForCell(addr) // computed qiymatni static’ga
        } catch (e) {
            console.error('Formula error', addr, exprStable, e)
        }
    }
}


let _unpin = null

function pinRowsSticky(rows = [1, 2, 4, 5]) {
    if (_unpin) { _unpin(); _unpin = null }

    const content = sheetEl.value?.querySelector('.jexcel_content')
    const tbody = content?.querySelector('tbody')
    if (!content || !tbody) return

    const BASE_Z = 1000
    const restore = []

    // 1) Ustun sarlavhalari (A,B,...) ni sticky qilish
    const headerRow =
        content.querySelector('thead tr') ||
        content.querySelector('.jexcel_headers tr') ||
        content.querySelector('.jexcel_headers')

    const headerCells = headerRow ? Array.from(headerRow.children) : []
    const headerH = headerRow
        ? (headerRow.getBoundingClientRect().height || headerRow.offsetHeight || 28)
        : 28

    if (headerRow) {
        headerCells.forEach(td => {
            const old = { position: td.style.position, top: td.style.top, z: td.style.zIndex, bg: td.style.backgroundColor, boxShadow: td.style.boxShadow }
            restore.push(() => {
                td.style.position = old.position
                td.style.top = old.top
                td.style.zIndex = old.z
                td.style.backgroundColor = old.bg
                td.style.boxShadow = old.boxShadow
            })
            td.style.position = 'sticky'
            td.style.top = '0px'
            td.style.zIndex = String(BASE_Z + rows.length + 1)
            td.style.backgroundColor = '#eaf2fb'
            td.style.boxShadow = '0 1px 0 rgba(0,0,0,.08)'
        })
    }

    // 2) Belgilangan qatorlarni sarlavha PASTIDAN sticky qilish
    let top = headerH
    const trs = rows.map(r => tbody.children[r - 1]).filter(Boolean)
    trs.forEach((tr, i) => {
        const h = tr.getBoundingClientRect().height || tr.offsetHeight
        Array.from(tr.children).forEach(td => {
            const old = { position: td.style.position, top: td.style.top, z: td.style.zIndex, bg: td.style.backgroundColor, boxShadow: td.style.boxShadow }
            restore.push(() => {
                td.style.position = old.position
                td.style.top = old.top
                td.style.zIndex = old.z
                td.style.backgroundColor = old.bg
                td.style.boxShadow = old.boxShadow
            })
            td.style.position = 'sticky'
            td.style.top = `${top}px`
            td.style.zIndex = String(BASE_Z + i)
            td.style.backgroundColor = '#eef7ff'
            td.style.boxShadow = '0 1px 0 rgba(0,0,0,.08)'
        })
        top += h
    })

    const onScroll = () => {
        const scrolled = content.scrollTop > 0
            ;[...headerCells, ...trs.flatMap(tr => Array.from(tr.children))].forEach(td => {
                td.style.boxShadow = scrolled ? '0 2px 4px rgba(0,0,0,.06)' : '0 1px 0 rgba(0,0,0,.08)'
            })
    }
    content.addEventListener('scroll', onScroll)

    _unpin = () => {
        content.removeEventListener('scroll', onScroll)
        restore.forEach(fn => fn())
    }
}




async function postStaticForCell(addr, { toast = false } = {}) {
    const m = addr.match(/^([A-Z]+)(\d+)$/)
    if (!m) return
    const colLtr = m[1], rowIdx = Number(m[2])
    const x = colLetterToIndex(colLtr)
    const y = rowIdx - 1

    const meta = paramRowMap.value.get(rowIdx)
    if (!meta) return

    const pmeta = periodCols.value.get(x) // { id, label }
    if (!pmeta) return

    // qiymat
    let raw = jexcel.getValueFromCoords(x, y, false); // avval xom qiymat
    if (raw == null || raw === '') raw = jexcel.getValueFromCoords(x, y, true);

    const value = toNumber(raw);
    if (value === null) return;

    // period oraliqlari
    const { start: period_start_date, end: period_end_date } =
        periodRangeFor(pmeta.label, date.value)

    await axios.post('/static-params/upsert', {
        number_page: numberPage,
        factory_structure_id: meta.fsId,
        parameter_id: meta.parameterId,
        group_id: meta.groupId,
        period_type_id: pmeta.id,
        period_start_date,
        period_end_date,
        value,
        comment: null,               // endi slotlar yo'q, commentni xohlasangiz null
        for_date: ymd(date.value)
    })

    if (toast) init({ message: 'Maʼlumot saqlandi', color: 'success' })
}
function toNumber(val) {
    if (typeof val === 'number') return val;
    if (val == null) return null;

    // Matnni tozalash
    let s = String(val).trim().replace(/\u00A0/g, ''); // NBSP -> ''
    // Oxirgi ajratuvchi – o‘nlik, qolganlari minglik deb olinadi
    const lastDot = s.lastIndexOf('.');
    const lastComma = s.lastIndexOf(',');
    const decimalSep = Math.max(lastDot, lastComma) === -1
        ? '' : (lastDot > lastComma ? '.' : ',');

    // Bo‘sh joylarni olib tashlaymiz
    s = s.replace(/\s+/g, '');

    // Minglik ajratuvchini olib tashlaymiz
    if (decimalSep === ',') s = s.replace(/\./g, '');
    if (decimalSep === '.') s = s.replace(/,/g, '');

    // O‘nlik ajratuvchini '.' ga aylantiramiz
    if (decimalSep) s = s.replace(decimalSep, '.');

    const n = Number(s);
    return Number.isFinite(n) ? n : null;
}


// yuklash va rebuild
onMounted(async () => {
    await Promise.all([fetchStaticParams(), fetchPeriodTypes()])
    build()
})
watch(date, build)
watch([staticItems, periodTypes], build)
</script>
<style>
/* Vuestic popover/pickerlarni hamma narsadan yuqoriga qo'yamiz */
.va-dropdown__content,
.va-popover__content,
.va-date-picker__panel {
    z-index: 99999 !important;
}
</style>
