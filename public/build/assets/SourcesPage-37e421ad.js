import{c as B,r as u,d as I,H as v,x as g,o as E,f as r,g as F,h as M,i as m,k as n,l as h,j as s,m as x,t as T}from"./app-cc23be06.js";/* empty css                   */import{_ as U,a as A}from"./EditSource-f95cb9c9.js";const $={class:"grid grid-rows-[55px,1fr]"},j={class:"flex justify-between"},q=m("span",{class:"flex w-full"},null,-1),z={class:"va-h3"},G={class:"flex-grow"},J={__name:"SourcesPage",setup(O){const{locale:c,t:o}=B(),i=u([]),f=u(null),d=u(!1),l=I({Name:"",ShortName:"",Comment:""});function V(e){f.value.applyTransaction({remove:[e]})}function w(e,t){e.setData(t)}v("ondeleted",V),v("onupdated",w);const N=g(()=>[{headerName:o("table.headerRow"),valueGetter:"node.rowIndex + 1"},{headerName:o("table.name"),field:"Name",flex:1},{headerName:o("table.shortName"),field:"ShortName"},{headerName:o("table.comment"),field:"Comment",flex:1},{cellClass:["px-0"],headerName:"",field:"",width:70,cellRenderer:U},{cellClass:["px-0"],headerName:"",field:"",width:70,cellRenderer:A}]),b={sortable:!0,filter:!0},p=async()=>{try{const e=await x.get("/source");i.value=Array.isArray(e.data)?e.data:e.data.items}catch(e){console.error("Error fetching data:",e)}},y=async()=>{try{const{data:e}=await x.post("/source",l);e.status===200?(d.value=!1,l.Name="",l.ShortName="",l.Comment="",await p()):console.error("Error saving data:",e.message)}catch(e){console.error("Error saving data:",e)}};return E(()=>{const e=localStorage.getItem("locale");e&&(c.value=e),p()}),g(()=>c.value==="uz"?"Русский":"O‘zbek"),(e,t)=>{const C=r("VaButton"),_=r("VaInput"),D=r("VaTextarea"),S=r("VaForm"),k=r("VaModal"),R=r("ag-grid-vue");return F(),M("div",$,[m("main",null,[m("div",j,[q,n(C,{onClick:t[0]||(t[0]=a=>d.value=!0),class:"w-14 h-12 mt-1 mr-1",icon:"add"})]),n(k,{modelValue:d.value,"onUpdate:modelValue":t[4]||(t[4]=a=>d.value=a),"ok-text":s(o)("buttons.save"),"cancel-text":s(o)("buttons.cancel"),onOk:y,"close-button":""},{default:h(()=>[m("h3",z,T(s(o)("modals.addSourceTitle")),1),m("div",null,[n(S,{ref:"formRef",class:"flex flex-col items-baseline gap-2"},{default:h(()=>[n(_,{class:"w-full",modelValue:l.Name,"onUpdate:modelValue":t[1]||(t[1]=a=>l.Name=a),rules:[a=>a&&a.length>0||s(o)("form.requiredField")],label:s(o)("form.name")},null,8,["modelValue","rules","label"]),n(_,{class:"w-full",modelValue:l.ShortName,"onUpdate:modelValue":t[2]||(t[2]=a=>l.ShortName=a),rules:[a=>a&&a.length>0||s(o)("form.requiredField")],label:s(o)("form.shortName")},null,8,["modelValue","rules","label"]),n(D,{class:"w-full",modelValue:l.Comment,"onUpdate:modelValue":t[3]||(t[3]=a=>l.Comment=a),"max-length":"125",label:s(o)("form.comment")},null,8,["modelValue","label"])]),_:1},512)])]),_:1},8,["modelValue","ok-text","cancel-text"])]),m("main",G,[n(R,{rowData:i.value,columnDefs:N.value,defaultColDef:b,animateRows:"true",class:"ag-theme-material h-full",onGridReady:t[5]||(t[5]=a=>f.value=a.api)},null,8,["rowData","columnDefs"])])])}}};export{J as default};