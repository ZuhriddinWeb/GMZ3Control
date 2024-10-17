import{u as k,c as I,r as h,G as q,f,g as U,h as F,k as d,j as a,D as $,l as R,i as c,t as D,m as x,d as z,I as H,V as G,n as g,H as M,x as B,o as L}from"./app-d9ae6f7d.js";/* empty css                   */const J={class:"h-full w-full text-center content-center"},K={class:"va-h3"},Q={__name:"DeleteBlog",props:["params"],setup(_){const{init:S}=k(),{t:m}=I(),l=h(!1),v=_,V=q("ondeleted"),u=async()=>{try{const{data:p}=await x.delete(`/blogs/${v.params.data.id}`);p.status===200?(V(v.params.data),l.value=!1,S({message:m("login.successMessage"),color:"success"})):console.error("Error deleting data:",p.message)}catch(p){console.error("Error deleting data:",p)}};return(p,e)=>{const w=f("VaModal");return U(),F("main",J,[d(a($),{round:"",icon:"delete",preset:"primary",class:"mt-1",onClick:e[0]||(e[0]=N=>l.value=!0)}),d(w,{modelValue:l.value,"onUpdate:modelValue":e[1]||(e[1]=N=>l.value=N),"ok-text":a(m)("modals.apply"),"cancel-text":a(m)("modals.cancel"),onClose:e[2]||(e[2]=N=>l.value=!1),onOk:u,"close-button":""},{default:R(()=>[c("h3",K,D(a(m)("modals.title")),1),c("p",null,D(a(m)("modals.message")),1)]),_:1},8,["modelValue","ok-text","cancel-text"])])}}},W={class:"h-full w-full text-center content-center"},X={class:"grid grid-cols-1 md:grid-cols-1 gap-2 items-end w-full"},Y={__name:"EditBlog",props:["params"],setup(_){const{init:S}=k(),m=_,l=h(!1),v=q("onupdated"),V=h([]),{t:u,locale:p}=I(),e=z({StructureID:"",Name:"",NameRus:"",ShortNameRus:"",ShortName:"",Comment:"",id:m.params.data.id}),w=async()=>{try{const i=await x.get("/structure");V.value=i.data.map(b=>({value:b.id,text:p.value==="uz"?b.Name:b.NameRus}));const s=await x.get(`blogs/${m.params.data.id}`);e.StructureID=+s.data.StructureID,e.Name=s.data.Name,e.ShortName=s.data.ShortName,e.Comment=s.data.Comment}catch(i){console.error("Error fetching graphics data:",i)}},N=async()=>{try{const{data:i}=await x.put("/blogs",e);i.status===200?(v(m.params.node,i.unit),l.value=!1,S({message:u("login.successMessage"),color:"success"})):console.error("Error saving data:",i.message)}catch(i){console.error("Error saving data:",i)}};return H(()=>e.StructureID,i=>{}),(i,s)=>{const b=f("VaSelect"),y=f("VaTextarea"),C=f("VaModal");return U(),F("main",W,[d(a($),{round:"",icon:"edit",preset:"primary",class:"mt-1",onClick:s[0]||(s[0]=o=>(l.value=!0,w))}),d(C,{modelValue:l.value,"onUpdate:modelValue":s[7]||(s[7]=o=>l.value=o),"ok-text":a(u)("modals.apply"),"cancel-text":a(u)("modals.cancel"),onOk:N,onClose:s[8]||(s[8]=o=>l.value=!1),"close-button":""},{default:R(()=>[c("h3",{class:"va-h3",onVnodeMounted:w},D(a(u)("modals.editFactory")),513),c("div",null,[d(a(G),{ref:"formRef",class:"flex flex-col items-baseline gap-1"},{default:R(()=>[c("div",X,[d(b,{modelValue:e.StructureID,"onUpdate:modelValue":s[1]||(s[1]=o=>e.StructureID=o),"value-by":"value",class:"mb-1",label:a(u)("form.structureName"),options:V.value,clearable:""},null,8,["modelValue","label","options"])]),d(a(g),{class:"w-full",modelValue:e.Name,"onUpdate:modelValue":s[2]||(s[2]=o=>e.Name=o),rules:[o=>!!o||a(u)("validation.requiredField")],label:a(u)("form.name")},null,8,["modelValue","rules","label"]),d(a(g),{class:"w-full",modelValue:e.NameRus,"onUpdate:modelValue":s[3]||(s[3]=o=>e.NameRus=o),rules:[o=>!!o||a(u)("validation.requiredField")],label:a(u)("form.nameRus")},null,8,["modelValue","rules","label"]),d(a(g),{class:"w-full",modelValue:e.ShortName,"onUpdate:modelValue":s[4]||(s[4]=o=>e.ShortName=o),rules:[o=>!!o||a(u)("validation.requiredField")],label:a(u)("form.shortName")},null,8,["modelValue","rules","label"]),d(a(g),{class:"w-full",modelValue:e.ShortNameRus,"onUpdate:modelValue":s[5]||(s[5]=o=>e.ShortNameRus=o),rules:[o=>!!o||a(u)("validation.requiredField")],label:a(u)("form.shortNameRus")},null,8,["modelValue","rules","label"]),d(y,{class:"w-full",modelValue:e.Comment,"onUpdate:modelValue":s[6]||(s[6]=o=>e.Comment=o),"max-length":125,label:a(u)("form.comment")},null,8,["modelValue","label"])]),_:1},512)])]),_:1},8,["modelValue","ok-text","cancel-text"])])}}};const Z={class:"grid grid-rows-[55px,1fr]"},ee={class:"flex justify-between"},ae=c("span",{class:"flex w-full"},null,-1),le={class:"va-h3"},te={class:"grid grid-cols-1 md:grid-cols-1 gap-2 items-end w-full"},oe={class:"flex-grow"},ne={__name:"BlogsPage",setup(_){const{init:S}=k(),{locale:m,t:l}=I(),v=h([]),V=h(null),u=h(!1),p=h([]),e=z({StructureID:"",Name:"",NameRus:"",ShortName:"",ShortNameRus:"",Comment:""});function w(n){V.value.applyTransaction({remove:[n]})}function N(n,r){n.setData(r)}M("ondeleted",w),M("onupdated",N);const i=B(()=>[{headerName:l("table.headerRow"),valueGetter:"node.rowIndex + 1"},{headerName:l("table.structure"),field:C(),flex:1},{headerName:l("table.name"),field:b(),flex:1},{headerName:l("table.shortName"),field:y()},{cellClass:["px-0"],headerName:"",field:"",width:70,cellRenderer:Y},{cellClass:["px-0"],headerName:"",field:"",width:70,cellRenderer:Q}]),s={sortable:!0,filter:!0},b=()=>m.value==="uz"?"Name":"NameRus",y=()=>m.value==="uz"?"ShortName":"ShortNameRus",C=()=>m.value==="uz"?"Name":"NameRus",o=async()=>{try{const n=await x.get("/blogs");v.value=Array.isArray(n.data)?n.data:n.data.items}catch(n){console.error("Error fetching data:",n)}},E=async()=>{try{const n=await x.get("/structure");p.value=n.data.map(r=>({value:r.id,text:m.value==="uz"?r.Name:r.NameRus}))}catch(n){console.error("Error fetching graphics data:",n)}},T=async()=>{try{const{data:n}=await x.post("/blogs",e);n.status===200?(u.value=!1,e.StructureID="",e.Name="",e.NameRus="",e.ShortName="",e.ShortNameRus="",e.Comment="",await o(),S({message:l("login.successMessage"),color:"success"})):console.error("Error saving data:",n.message)}catch(n){console.error("Error saving data:",n)}};return L(()=>{const n=localStorage.getItem("locale");n&&(m.value=n),o(),E()}),B(()=>m.value==="uz"?"Русский":"O‘zbek"),(n,r)=>{const O=f("VaSelect"),j=f("VaTextarea"),A=f("VaModal"),P=f("ag-grid-vue");return U(),F("div",Z,[c("main",null,[c("div",ee,[ae,d(a($),{onClick:r[0]||(r[0]=t=>(u.value=!0,E)),class:"w-14 h-12 mt-1 mr-1",icon:"add"})]),d(A,{modelValue:u.value,"onUpdate:modelValue":r[7]||(r[7]=t=>u.value=t),"ok-text":a(l)("buttons.save"),"cancel-text":a(l)("buttons.cancel"),onOk:T,"close-button":""},{default:R(()=>[c("h3",le,D(a(l)("modals.addBlogTitle")),1),c("div",null,[d(a(G),{ref:"formRef",class:"flex flex-col items-baseline gap-1"},{default:R(()=>[c("div",te,[d(O,{modelValue:e.StructureID,"onUpdate:modelValue":r[1]||(r[1]=t=>e.StructureID=t),"value-by":"value",class:"mb-1",label:a(l)("form.structureName"),options:p.value,clearable:""},null,8,["modelValue","label","options"])]),d(a(g),{class:"w-full",modelValue:e.Name,"onUpdate:modelValue":r[2]||(r[2]=t=>e.Name=t),rules:[t=>t&&t.length>0||a(l)("validation.requiredField")],label:a(l)("form.name")},null,8,["modelValue","rules","label"]),d(a(g),{class:"w-full",modelValue:e.NameRus,"onUpdate:modelValue":r[3]||(r[3]=t=>e.NameRus=t),rules:[t=>t&&t.length>0||a(l)("validation.requiredField")],label:a(l)("form.nameRus")},null,8,["modelValue","rules","label"]),d(a(g),{class:"w-full",modelValue:e.ShortName,"onUpdate:modelValue":r[4]||(r[4]=t=>e.ShortName=t),rules:[t=>t&&t.length>0||a(l)("validation.requiredField")],label:a(l)("form.shortName")},null,8,["modelValue","rules","label"]),d(a(g),{class:"w-full",modelValue:e.ShortNameRus,"onUpdate:modelValue":r[5]||(r[5]=t=>e.ShortNameRus=t),rules:[t=>t&&t.length>0||a(l)("validation.requiredField")],label:a(l)("form.shortNameRus")},null,8,["modelValue","rules","label"]),d(j,{class:"w-full",modelValue:e.Comment,"onUpdate:modelValue":r[6]||(r[6]=t=>e.Comment=t),"max-length":"125",label:a(l)("form.comment")},null,8,["modelValue","label"])]),_:1},512)])]),_:1},8,["modelValue","ok-text","cancel-text"])]),c("main",oe,[d(P,{rowData:v.value,columnDefs:i.value,defaultColDef:s,animateRows:"true",class:"ag-theme-material h-full",onGridReady:r[8]||(r[8]=t=>V.value=t.api)},null,8,["rowData","columnDefs"])])])}}};export{ne as default};
