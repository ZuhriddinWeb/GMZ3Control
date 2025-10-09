<template>
    <div class="p-3 pt-28">
        <div class="flex items-center gap-2 mb-2">
            <VaInput v-model="formulaBar" class="max-w-[700px] w-full" placeholder="" @keyup.enter="applyFormulaBar" />
            <VaButton size="small" @click="applyFormulaBar" :disabled="!selectedCell">
                Formula’ni saqla
            </VaButton>
            <span v-if="selectedCell" class="text-gray-500 text-sm">Katak: {{ selectedCell }}</span>
        </div>
        <div class="flex items-center gap-3 mb-3">
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
function resolvePeriodLabels() {
    // 1) periodType -> Map(id -> name)
    const map = new Map(
        (periodTypes.value || []).map(pt => [String(pt.id), String(pt.name || '').trim()])
    )

    // 2) Sahifadagi ishlatilgan period id lar
    const usedIds = [...new Set(
        (staticItems.value || [])
            .map(x => x?.period_type_id)
            .filter(v => v !== undefined && v !== null)
            .map(String)
    )]

    // 3) Normallashtirish (uz/ru sinonimlarga tayyor)
    const normalize = (raw) => {
        const n = String(raw || '').trim().toLowerCase()
        if (['kun', 'kunlik', 'sutka', 'sutkalik'].includes(n)) return 'Kunlik'
        if (['oy', 'oylik', 'mes', 'mesyachniy', 'mesyachnyy', 'mesyach'].includes(n)) return 'Oylik'
        // default — 1-harfi katta
        return raw ? raw.charAt(0).toUpperCase() + raw.slice(1) : ''
    }

    // 4) Semantik qidiruv
    const findByWord = (word) => {
        const hitId = usedIds.find(id => new RegExp(word, 'i').test(map.get(id) || ''))
        return hitId ? normalize(map.get(hitId)) : null
    }

    let dailyLabel = findByWord('kun|sutk')
    let monthlyLabel = findByWord('oy|mes')

    // 5) Zaxira strategiya: id bo‘yicha (1 → Kunlik, 2 → Oylik)
    if (!dailyLabel && usedIds.includes('1')) dailyLabel = 'Kunlik'
    if (!monthlyLabel && usedIds.includes('2')) monthlyLabel = 'Oylik'

    // 6) Hali ham topilmasa — tartib bo‘yicha ajratamiz
    if (!dailyLabel || !monthlyLabel) {
        const sorted = [...usedIds].sort((a, b) => Number(a) - Number(b))
        if (!dailyLabel && sorted[0]) dailyLabel = normalize(map.get(sorted[0]) || 'Kunlik')
        if (!monthlyLabel) {
            const second = sorted.find(id => id !== sorted[0]) || sorted[0]
            monthlyLabel = normalize(map.get(second) || 'Oylik')
        }
    }

    // 7) Ikkalasi bir xil bo‘lib qolsa — farqlab qo‘yamiz
    if (dailyLabel === monthlyLabel) {
        // Agar birortasi "Kunlik" bo‘lsa, ikkinchisini "Oylik" qilamiz
        if (dailyLabel === 'Kunlik') monthlyLabel = 'Oylik'
        else if (monthlyLabel === 'Oylik') dailyLabel = 'Kunlik'
        else {
            dailyLabel = 'Kunlik'
            monthlyLabel = 'Oylik'
        }
    }

    return { dailyLabel, monthlyLabel }
}
const toNum = (v, fallback = Number.POSITIVE_INFINITY) => {
    const n = Number(v)
    return Number.isFinite(n) ? n : fallback
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
            merge('C4:E4'),
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
    const { dailyLabel, monthlyLabel } = resolvePeriodLabels()
    console.log(dailyLabel, monthlyLabel);

    jexcel.setValue('C4', dailyLabel)
    jexcel.setValue('C5', 'reja')
    jexcel.setValue('D4', dailyLabel)
    jexcel.setValue('D5', 'fakt/20:08')
    jexcel.setValue('E4', dailyLabel)
    jexcel.setValue('E5', 'fakt/08:20')
    jexcel.setValue('F4', dailyLabel)
    jexcel.setValue('F5', 'fakt jami')
    jexcel.setValue('G4', '')      // ustun nomi yo'q, faqat pastda %
    jexcel.setValue('G5', '%')

    jexcel.setValue('H4', monthlyLabel)
    jexcel.setValue('H5', 'reja')
    jexcel.setValue('I4', monthlyLabel)
    jexcel.setValue('I5', 'fakt')
    jexcel.setValue('J4', monthlyLabel) // agar bu ham oylik foiz bo'lsa, oylik nomini ko'rsatamiz
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
function colToPeriodType(colIndex) {
    // 0-based index -> harf
    const Ltr = L(colIndex)
    if (['C', 'D', 'E', 'F', 'G'].includes(Ltr)) return 'daily'
    if (['H', 'I', 'J'].includes(Ltr)) return 'monthly'
    return null
}
async function fillExistingStaticValues() {
    // static-with-numberpage all rows:
    const rows = staticItems.value || []
    if (!rows.length) return

    const { dailyId, monthlyId } = resolvePeriodIds()
    const today = ymd(date.value)
    const { start: monthStart, end: monthEnd } = monthRange(date.value)

    // rowIndex’ni topish uchun paramId->rowIndex map
    const paramToRow = new Map()
    for (const [rowIndex, meta] of paramRowMap.value.entries()) {
        paramToRow.set(String(meta.parameterId), rowIndex)
    }

    // onchange ni bosmaslik uchun
    const prev = applyingFormulas
    applyingFormulas = true

    for (const x of rows) {
        const pid = String(x.ParameterID || '')
        const rowIndex = paramToRow.get(pid)
        if (!rowIndex) continue

        const typeId = Number(x.period_type_id)
        const start = String(x.period_start_date || '')
        const end = String(x.period_end_date || start || '')

        // Hozirgi sana shu period ichidami?
        const inDaily = (typeId === dailyId) && (start === today && end === today)
        const inMonthly = (typeId === monthlyId) && (start === monthStart && end === monthEnd)

        if (!inDaily && !inMonthly) continue

        const slot = x.Comment || (typeId === dailyId ? 'daily_plan' : 'monthly_plan')
        const col = slotToCol(slot)
        if (!col) continue

        jexcel.setValue(`${col}${rowIndex}`, x.value)
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










// --- QATORLARNI /static dan FS->Group->Param bo‘yicha to‘ldirish
async function evaluateFormula(expr, dateObj, { currentCell } = {}) {
    let s = String(expr).trim()

    // P(<param_id>,"slot") -> A1
    let defaultSlot = null
    if (currentCell) {
        const m = currentCell.match(/^([A-Z]+)\d+$/)
        if (m) defaultSlot = colToSlot(m[1])
    }
    s = s.replace(/P\(\s*([0-9A-F-]{8,})\s*(?:,\s*"(daily_plan|daily_fact_20_08|daily_fact_08_20|daily_fact_total|daily_percent|monthly_plan|monthly_fact|monthly_percent)")?\s*\)/gi,
        (_, pid, slotRaw) => {
            const row = paramIdToRow.value.get(String(pid).toUpperCase())
            const col = slotToCol(slotRaw || defaultSlot || 'daily_plan')
            return (row && col) ? `${col}${row}` : '0'
        })

    // REF(304,"B6") / 304!B6 -> son
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
    let s = String(expr || '')
    // faqat C..J oralig’i
    s = s.replace(/\b([CDEFGHIJ])(\d+)\b/g, (m, col, rowStr) => {
        const row = Number(rowStr)
        const meta = paramRowMap.value.get(row)
        if (!meta?.parameterId) return m
        const slot = colToSlot(col)
        if (!slot) return m
        return `P(${meta.parameterId},"${slot}")`
    })
    return s
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
    // maqsad (chap tomondagi) parametr va slot
    const rowIdx = Number(cellAddr.match(/\d+/)?.[0] || 0)
    const colLtr = String(cellAddr.match(/^[A-Z]+/)?.[0] || '')
    const meta = paramRowMap.value.get(rowIdx)
    const slot = colToSlot(colLtr)

    if (!meta?.parameterId || !slot) {
        console.warn('Parametr yoki slot topilmadi', cellAddr, meta, slot)
        return
    }

    // stabil ko‘rinishga o‘giramiz
    const exprStable = toStableFormula(exprRaw)
    formulas.value[cellAddr] = exprStable

    // DBga parametrga bog‘lab yuboramiz
    await axios.post('/sheet/formula', {
        numberPage,
        number_page: numberPage,
        // ikkala nom ham – backend qaysi birini olishi noaniq bo‘lsa
        param_id: meta.parameterId,
        parameter_id: meta.parameterId,
        slot,                          // ← qaysi ustun uchun
        cell: cellAddr,                // ← diag. uchun qoldiramiz
        expr: exprStable,              // asosiy ustun
        expr_stable: exprStable,       // agar backend saqlasa
        expr_raw: exprRaw,             // foydalanuvchi kiritgani
        scope: 'permanent',
        date: ymd(date.value),
        for_date: null,
    })

    // Jadvalga qo‘yish (tokenlarni kengaytirib)
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
        // maqsad katakni topamiz
        let rowIndex = null, colLtr = null

        if (f.param_id || f.parameter_id) {
            const pid = String(f.param_id ?? f.parameter_id)
            rowIndex = paramIdToRow.value.get(pid)
            colLtr = f.slot ? slotToCol(f.slot) : (String(f.cell || '').match(/^[A-Z]+/)?.[0] || null)
        } else if (f.cell) {
            // legacy: cell bo‘yicha
            const m = String(f.cell).match(/^([A-Z]+)(\d+)$/)
            if (m) { colLtr = m[1]; rowIndex = Number(m[2]) }
        }

        if (!rowIndex || !colLtr) continue
        const addr = `${colLtr}${rowIndex}`

        // ifodani stabil deb qabul qilamiz; bo‘lmasa ham hozirgi tartib bo‘yicha stabilga o‘girib yuboramiz
        const exprStable = f.expr_stable || f.expr || ''
        formulas.value[addr] = exprStable

        try {
            const compiled = await evaluateFormula(exprStable, date.value, { currentCell: addr })
            applyingFormulas = true
            jexcel.setValue(addr, compiled)
            applyingFormulas = false
            jexcel.setStyle({ [addr]: 'border:1px dashed #444;font-weight:600;' })
            await postStaticForCell(addr)   // computed qiymatni static’ga ham yozib qo‘yish
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
    const x = ((Ltr) => { let v = 0; for (const ch of Ltr) v = v * 26 + (ch.charCodeAt(0) - 64); return v - 1 })(colLtr)
    const y = rowIdx - 1

    const meta = paramRowMap.value.get(rowIdx)
    if (!meta) return

    const { dailyId, monthlyId } = resolvePeriodIds()
    const kind = colToPeriodType(x)
    const period_type_id = (kind === 'daily') ? dailyId : monthlyId

    // ✅ FORMULA NATIJASINI OLISH (processed = true)
    let v = jexcel.getValueFromCoords(x, y, true)
    if (typeof v === 'string') v = v.replace(',', '.')
    const num = parseFloat(v)
    const value = Number.isFinite(num) ? num : null

    // Agar natija yo'q bo'lsa, yozmaslik
    if (value === null) return

    // Period oraliqlari
    let period_start_date, period_end_date
    if (kind === 'daily') {
        const d = ymd(date.value)
        period_start_date = d
        period_end_date = d
    } else {
        const { start, end } = monthRange(date.value)
        period_start_date = start
        period_end_date = end
    }

    const comment = colToSlot(colLtr)

    const res = await axios.post('/static-params/upsert', {
        number_page: numberPage,
        factory_structure_id: meta.fsId,
        parameter_id: meta.parameterId,
        group_id: meta.groupId,
        period_type_id,
        period_start_date,
        period_end_date,
        value,
        comment,
        for_date: ymd(date.value)
    })
    if (toast && res && res.status >= 200 && res.status < 300) {
        // i18n ishlatsa:
        // init({ message: t('login.successMessage'), color: 'success' })
        init({ message: 'Maʼlumot saqlandi', color: 'success' })
    }
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
