import{u as E,c as M,r as m,d as T,H as V,x as b,o as q,f as i,g as G,h as A,i as n,k as r,j as o,l as _,m as f,D as O,t as $,V as j,n as c}from"./app-726a9268.js";/* empty css                   */import{_ as P,a as H}from"./EditBlog-3c181fea.js";const L={class:"grid grid-rows-[55px,1fr]"},J={class:"flex justify-between"},K=n("span",{class:"flex w-full"},null,-1),Q={class:"va-h3"},W={class:"grid grid-cols-1 md:grid-cols-1 gap-2 items-end w-full"},X={class:"flex-grow"},le={__name:"BlogsPage",setup(Y){const{init:w}=E(),{locale:u,t:s}=M(),p=m([]),g=m(null),d=m(!1),v=m([]),t=T({StructureID:"",Name:"",NameRus:"",ShortName:"",ShortNameRus:"",Comment:""});function x(l){g.value.applyTransaction({remove:[l]})}function R(l,a){l.setData(a)}V("ondeleted",x),V("onupdated",R);const S=b(()=>[{headerName:s("table.headerRow"),valueGetter:"node.rowIndex + 1"},{headerName:s("table.structure"),field:k(),flex:1},{headerName:s("table.name"),field:y(),flex:1},{headerName:s("table.shortName"),field:C()},{cellClass:["px-0"],headerName:"",field:"",width:70,cellRenderer:P},{cellClass:["px-0"],headerName:"",field:"",width:70,cellRenderer:H}]),D={sortable:!0,filter:!0},y=()=>u.value==="uz"?"Name":"NameRus",C=()=>u.value==="uz"?"ShortName":"ShortNameRus",k=()=>u.value==="uz"?"Name":"NameRus",N=async()=>{try{const l=await f.get("/blogs");p.value=Array.isArray(l.data)?l.data:l.data.items}catch(l){console.error("Error fetching data:",l)}},h=async()=>{try{const l=await f.get("/structure");v.value=l.data.map(a=>({value:a.id,text:u.value==="uz"?a.Name:a.NameRus}))}catch(l){console.error("Error fetching graphics data:",l)}},B=async()=>{try{const{data:l}=await f.post("/blogs",t);l.status===200?(d.value=!1,t.StructureID="",t.Name="",t.NameRus="",t.ShortName="",t.ShortNameRus="",t.Comment="",await N(),w({message:s("login.successMessage"),color:"success"})):console.error("Error saving data:",l.message)}catch(l){console.error("Error saving data:",l)}};return q(()=>{const l=localStorage.getItem("locale");l&&(u.value=l),N(),h()}),b(()=>u.value==="uz"?"Русский":"O‘zbek"),(l,a)=>{const F=i("VaSelect"),I=i("VaTextarea"),U=i("VaModal"),z=i("ag-grid-vue");return G(),A("div",L,[n("main",null,[n("div",J,[K,r(o(O),{onClick:a[0]||(a[0]=e=>(d.value=!0,h)),class:"w-14 h-12 mt-1 mr-1",icon:"add"})]),r(U,{modelValue:d.value,"onUpdate:modelValue":a[7]||(a[7]=e=>d.value=e),"ok-text":o(s)("buttons.save"),"cancel-text":o(s)("buttons.cancel"),onOk:B,"close-button":""},{default:_(()=>[n("h3",Q,$(o(s)("modals.addBlogTitle")),1),n("div",null,[r(o(j),{ref:"formRef",class:"flex flex-col items-baseline gap-1"},{default:_(()=>[n("div",W,[r(F,{modelValue:t.StructureID,"onUpdate:modelValue":a[1]||(a[1]=e=>t.StructureID=e),"value-by":"value",class:"mb-1",label:o(s)("form.structureName"),options:v.value,clearable:""},null,8,["modelValue","label","options"])]),r(o(c),{class:"w-full",modelValue:t.Name,"onUpdate:modelValue":a[2]||(a[2]=e=>t.Name=e),rules:[e=>e&&e.length>0||o(s)("validation.requiredField")],label:o(s)("form.name")},null,8,["modelValue","rules","label"]),r(o(c),{class:"w-full",modelValue:t.NameRus,"onUpdate:modelValue":a[3]||(a[3]=e=>t.NameRus=e),rules:[e=>e&&e.length>0||o(s)("validation.requiredField")],label:o(s)("form.nameRus")},null,8,["modelValue","rules","label"]),r(o(c),{class:"w-full",modelValue:t.ShortName,"onUpdate:modelValue":a[4]||(a[4]=e=>t.ShortName=e),rules:[e=>e&&e.length>0||o(s)("validation.requiredField")],label:o(s)("form.shortName")},null,8,["modelValue","rules","label"]),r(o(c),{class:"w-full",modelValue:t.ShortNameRus,"onUpdate:modelValue":a[5]||(a[5]=e=>t.ShortNameRus=e),rules:[e=>e&&e.length>0||o(s)("validation.requiredField")],label:o(s)("form.shortNameRus")},null,8,["modelValue","rules","label"]),r(I,{class:"w-full",modelValue:t.Comment,"onUpdate:modelValue":a[6]||(a[6]=e=>t.Comment=e),"max-length":"125",label:o(s)("form.comment")},null,8,["modelValue","label"])]),_:1},512)])]),_:1},8,["modelValue","ok-text","cancel-text"])]),n("main",X,[r(z,{rowData:p.value,columnDefs:S.value,defaultColDef:D,animateRows:"true",class:"ag-theme-material h-full",onGridReady:a[8]||(a[8]=e=>g.value=e.api)},null,8,["rowData","columnDefs"])])])}}};export{le as default};
