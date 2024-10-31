import{u as k,c as U,r as h,G as z,f as x,g as $,h as F,k as m,j as e,D as E,l as y,i as p,t as D,m as w,d as G,K as H,V as T,n as g,H as B,x as q,o as K}from"./app-deaa25d8.js";/* empty css                   */const L={class:"h-full w-full text-center content-center"},J={class:"va-h3"},Q={__name:"DeleteBlog",props:["params"],setup(S){const{init:R}=k(),{t:i}=U(),a=h(!1),v=S,V=z("ondeleted"),d=async()=>{try{const{data:f}=await w.delete(`/blogs/${v.params.data.id}`);f.status===200?(V(v.params.data),a.value=!1,R({message:i("login.successMessage"),color:"success"})):console.error("Error deleting data:",f.message)}catch(f){console.error("Error deleting data:",f)}};return(f,l)=>{const r=x("VaModal");return $(),F("main",L,[m(e(E),{round:"",icon:"delete",preset:"primary",class:"mt-1",onClick:l[0]||(l[0]=N=>a.value=!0)}),m(r,{modelValue:a.value,"onUpdate:modelValue":l[1]||(l[1]=N=>a.value=N),"ok-text":e(i)("modals.apply"),"cancel-text":e(i)("modals.cancel"),onClose:l[2]||(l[2]=N=>a.value=!1),onOk:d,"close-button":""},{default:y(()=>[p("h3",J,D(e(i)("modals.title")),1),p("p",null,D(e(i)("modals.message")),1)]),_:1},8,["modelValue","ok-text","cancel-text"])])}}},W={class:"h-full w-full text-center content-center"},X={class:"grid grid-cols-1 md:grid-cols-1 gap-2 items-end w-full"},Y={__name:"EditBlog",props:["params"],setup(S){const{init:R}=k(),i=S,a=h(!1),v=z("onupdated"),V=h([]),{t:d,locale:f}=U(),l=G({StructureID:"",Name:"",NameRus:"",ShortNameRus:"",ShortName:"",Comment:"",id:i.params.data.id}),r=async()=>{try{const c=await w.get("/structure");V.value=c.data.map(b=>({value:b.id,text:f.value==="uz"?b.Name:b.NameRus}));const o=await w.get(`blogs/${i.params.data.id}`);l.StructureID=+o.data.StructureID,l.Name=o.data.Name,l.ShortName=o.data.ShortName,l.Comment=o.data.Comment}catch(c){console.error("Error fetching graphics data:",c)}},N=async()=>{try{const{data:c}=await w.put("/blogs",l);c.status===200?(v(i.params.node,c.unit),a.value=!1,R({message:d("login.successMessage"),color:"success"})):console.error("Error saving data:",c.message)}catch(c){console.error("Error saving data:",c)}};return H(()=>l.StructureID,c=>{}),(c,o)=>{const b=x("VaSelect"),_=x("VaTextarea"),C=x("VaModal");return $(),F("main",W,[m(e(E),{round:"",icon:"edit",preset:"primary",class:"mt-1",onClick:o[0]||(o[0]=s=>(a.value=!0,r))}),m(C,{modelValue:a.value,"onUpdate:modelValue":o[7]||(o[7]=s=>a.value=s),"ok-text":e(d)("modals.apply"),"cancel-text":e(d)("modals.cancel"),onOk:N,onClose:o[8]||(o[8]=s=>a.value=!1),"close-button":""},{default:y(()=>[p("h3",{class:"va-h3",onVnodeMounted:r},D(e(d)("modals.editFactory")),513),p("div",null,[m(e(T),{ref:"formRef",class:"flex flex-col items-baseline gap-1"},{default:y(()=>[p("div",X,[m(b,{modelValue:l.StructureID,"onUpdate:modelValue":o[1]||(o[1]=s=>l.StructureID=s),"value-by":"value",class:"mb-1",label:e(d)("form.structureName"),options:V.value,clearable:""},null,8,["modelValue","label","options"])]),m(e(g),{class:"w-full",modelValue:l.Name,"onUpdate:modelValue":o[2]||(o[2]=s=>l.Name=s),rules:[s=>!!s||e(d)("validation.requiredField")],label:e(d)("form.name")},null,8,["modelValue","rules","label"]),m(e(g),{class:"w-full",modelValue:l.NameRus,"onUpdate:modelValue":o[3]||(o[3]=s=>l.NameRus=s),rules:[s=>!!s||e(d)("validation.requiredField")],label:e(d)("form.nameRus")},null,8,["modelValue","rules","label"]),m(e(g),{class:"w-full",modelValue:l.ShortName,"onUpdate:modelValue":o[4]||(o[4]=s=>l.ShortName=s),rules:[s=>!!s||e(d)("validation.requiredField")],label:e(d)("form.shortName")},null,8,["modelValue","rules","label"]),m(e(g),{class:"w-full",modelValue:l.ShortNameRus,"onUpdate:modelValue":o[5]||(o[5]=s=>l.ShortNameRus=s),rules:[s=>!!s||e(d)("validation.requiredField")],label:e(d)("form.shortNameRus")},null,8,["modelValue","rules","label"]),m(_,{class:"w-full",modelValue:l.Comment,"onUpdate:modelValue":o[6]||(o[6]=s=>l.Comment=s),"max-length":125,label:e(d)("form.comment")},null,8,["modelValue","label"])]),_:1},512)])]),_:1},8,["modelValue","ok-text","cancel-text"])])}}};const Z={class:"grid grid-rows-[55px,1fr]"},ee={class:"flex justify-between"},ae=p("span",{class:"flex w-full"},null,-1),le={class:"va-h3"},te={class:"flex-grow"},re={__name:"BlogsPage",props:{id:Number},setup(S){const{init:R}=k(),{locale:i,t:a}=U(),v=h([]),V=h(null),d=h(!1),f=h([]),l=S,r=G({StructureID:l.id,Name:"",NameRus:"",ShortName:"",ShortNameRus:"",Comment:""});function N(n){V.value.applyTransaction({remove:[n]})}function c(n,u){n.setData(u)}B("ondeleted",N),B("onupdated",c);const o=q(()=>[{headerName:a("table.headerRow"),valueGetter:"node.rowIndex + 1"},{headerName:a("table.structure"),field:s(),flex:1},{headerName:a("table.name"),field:_(),flex:1},{headerName:a("table.shortName"),field:C()},{cellClass:["px-0"],headerName:"",field:"",width:70,cellRenderer:Y},{cellClass:["px-0"],headerName:"",field:"",width:70,cellRenderer:Q}]),b={sortable:!0,filter:!0},_=()=>i.value==="uz"?"Name":"NameRus",C=()=>i.value==="uz"?"ShortName":"ShortNameRus",s=()=>i.value==="uz"?"Name":"NameRus",I=async()=>{try{const n=await w.get(`/getRowBlog/${l.id}`);v.value=Array.isArray(n.data)?n.data:n.data.items}catch(n){console.error("Error fetching data:",n)}},M=async()=>{try{const n=await w.get("/structure");f.value=n.data.map(u=>({value:u.id,text:i.value==="uz"?u.Name:u.NameRus}))}catch(n){console.error("Error fetching graphics data:",n)}},O=async()=>{try{const{data:n}=await w.post("/blogs",r);n.status===200?(d.value=!1,r.StructureID="",r.Name="",r.NameRus="",r.ShortName="",r.ShortNameRus="",r.Comment="",await I(),R({message:a("login.successMessage"),color:"success"})):console.error("Error saving data:",n.message)}catch(n){console.error("Error saving data:",n)}};return K(()=>{const n=localStorage.getItem("locale");n&&(i.value=n),I(),M()}),q(()=>i.value==="uz"?"Русский":"O‘zbek"),(n,u)=>{const j=x("VaTextarea"),A=x("VaModal"),P=x("ag-grid-vue");return $(),F("div",Z,[p("main",null,[p("div",ee,[ae,m(e(E),{onClick:u[0]||(u[0]=t=>(d.value=!0,M)),class:"w-14 h-12 mt-1 mr-1",icon:"add"})]),m(A,{modelValue:d.value,"onUpdate:modelValue":u[6]||(u[6]=t=>d.value=t),"ok-text":e(a)("buttons.save"),"cancel-text":e(a)("buttons.cancel"),onOk:O,"close-button":""},{default:y(()=>[p("h3",le,D(e(a)("modals.addBlogTitle")),1),p("div",null,[m(e(T),{ref:"formRef",class:"flex flex-col items-baseline gap-1"},{default:y(()=>[m(e(g),{class:"w-full",modelValue:r.Name,"onUpdate:modelValue":u[1]||(u[1]=t=>r.Name=t),rules:[t=>t&&t.length>0||e(a)("validation.requiredField")],label:e(a)("form.name")},null,8,["modelValue","rules","label"]),m(e(g),{class:"w-full",modelValue:r.NameRus,"onUpdate:modelValue":u[2]||(u[2]=t=>r.NameRus=t),rules:[t=>t&&t.length>0||e(a)("validation.requiredField")],label:e(a)("form.nameRus")},null,8,["modelValue","rules","label"]),m(e(g),{class:"w-full",modelValue:r.ShortName,"onUpdate:modelValue":u[3]||(u[3]=t=>r.ShortName=t),rules:[t=>t&&t.length>0||e(a)("validation.requiredField")],label:e(a)("form.shortName")},null,8,["modelValue","rules","label"]),m(e(g),{class:"w-full",modelValue:r.ShortNameRus,"onUpdate:modelValue":u[4]||(u[4]=t=>r.ShortNameRus=t),rules:[t=>t&&t.length>0||e(a)("validation.requiredField")],label:e(a)("form.shortNameRus")},null,8,["modelValue","rules","label"]),m(j,{class:"w-full",modelValue:r.Comment,"onUpdate:modelValue":u[5]||(u[5]=t=>r.Comment=t),"max-length":"125",label:e(a)("form.comment")},null,8,["modelValue","label"])]),_:1},512)])]),_:1},8,["modelValue","ok-text","cancel-text"])]),p("main",te,[m(P,{rowData:v.value,columnDefs:o.value,defaultColDef:b,animateRows:"true",class:"ag-theme-material h-full",onGridReady:u[7]||(u[7]=t=>V.value=t.api)},null,8,["rowData","columnDefs"])])])}}};export{re as default};
