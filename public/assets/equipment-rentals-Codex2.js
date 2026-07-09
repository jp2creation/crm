import{I as jsxRuntime,K as wrapModule,W as reactFactory}from"./index-CqSzWeas.js?v=2026070806";

var jsx=jsxRuntime();
var React=wrapModule(reactFactory(),1);

var styles=`
.equipment-module-shell {
  --theme-primary: 149 0 46;
  --theme-accent: 255 194 10;
  --calendar-line: #e4e9f1;
  --calendar-soft: #f7f9fc;
  --calendar-text: #223957;
  --period-morning-a: #e9fbf8;
  --period-morning-b: #d3f5ef;
  --period-morning-border: #55cbc5;
  --period-morning-accent: #10aaa4;
  --period-morning-text: #075d62;
  --period-afternoon-a: #fff0ed;
  --period-afternoon-b: #ffd6cf;
  --period-afternoon-border: #ff8b7d;
  --period-afternoon-accent: #ff5e52;
  --period-afternoon-text: #842921;
  --period-day-a: #5d7cff;
  --period-day-b: #3f5ee6;
  --period-day-border: #496bf1;
  --period-day-accent: #3856d9;
  color: var(--color-secondary-900);
}
.equipment-calendar-shell {
  border: 1px solid var(--calendar-line);
  background: #fff;
  box-shadow: 0 16px 42px rgba(15, 23, 42, .055);
}
.equipment-agenda-toolbar {
  background: linear-gradient(180deg, #fff 0%, #fbfcff 100%);
}
.equipment-agenda-nav,
.equipment-agenda-actions {
  min-width: 0;
}
.equipment-view-switch {
  display: inline-flex;
  align-items: center;
  gap: .25rem;
  border: 1px solid var(--calendar-line);
  border-radius: .8rem;
  background: var(--calendar-soft);
  padding: .22rem;
}
.equipment-view-switch .btn {
  border-color: transparent;
  box-shadow: none;
}
.equipment-view-switch .btn-primary {
  box-shadow: 0 8px 16px rgb(var(--theme-primary) / .18);
}
.equipment-calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 1px;
  background: var(--calendar-line);
  border-radius: 0 0 var(--radius-lg) var(--radius-lg);
  overflow: hidden;
}
.equipment-calendar-heading {
  background: #fff !important;
  color: var(--calendar-text) !important;
  letter-spacing: 0;
}
.equipment-calendar-cell {
  display: flex;
  min-height: 126px;
  flex-direction: column;
  background: #fff;
  border: 0;
  padding: .625rem;
  text-align: left;
}
.equipment-calendar-cell:hover {
  background: rgb(var(--theme-primary) / .05);
}
.equipment-calendar-cell-muted {
  background: var(--color-surface-50);
  color: var(--color-secondary-400);
}
.equipment-rental-card {
  position: relative;
  overflow: hidden;
  width: 100%;
  border: 1px solid var(--rental-border, var(--color-surface-200));
  border-radius: .55rem;
  background: var(--rental-bg, #fff);
  box-shadow: 0 8px 18px rgba(15, 23, 42, .035);
  color: var(--rental-text, var(--color-secondary-900));
  padding: .68rem .74rem;
  text-align: left;
  transition: border-color var(--duration-fast), box-shadow var(--duration-fast), transform var(--duration-fast);
}
.equipment-rental-card::before {
  content: "";
  position: absolute;
  left: .65rem;
  top: .75rem;
  width: .55rem;
  height: .55rem;
  border-radius: 999px;
  background: var(--rental-accent, rgb(var(--theme-primary)));
  box-shadow: 0 0 0 3px rgba(255, 255, 255, .45);
}
.equipment-rental-card:hover {
  border-color: var(--rental-accent, rgb(var(--theme-primary) / .45));
  box-shadow: 0 14px 30px rgba(15, 23, 42, .08);
  transform: translateY(-1px);
}
.equipment-rental-time {
  color: var(--rental-muted, var(--color-secondary-600));
  display: block;
  padding-left: 1.05rem;
}
.equipment-rental-title {
  color: var(--rental-text, var(--color-secondary-900));
}
.equipment-rental-details {
  color: var(--rental-muted, var(--color-secondary-500));
}
.equipment-rental-card[data-period="day"] .equipment-rental-time,
.equipment-rental-card[data-period="day"] .equipment-rental-title,
.equipment-rental-card[data-period="day"] .equipment-rental-details {
  color: #fff;
}
.equipment-calendar-events {
  position: relative;
  z-index: 2;
}
.equipment-calendar-events .equipment-rental-card {
  min-height: auto;
  border-radius: .7rem;
  box-shadow: none;
  padding: .52rem .58rem .52rem .62rem;
}
.equipment-calendar-events .equipment-rental-card::before {
  left: .45rem;
  top: .55rem;
  width: .42rem;
  height: .42rem;
}
.equipment-calendar-events .equipment-rental-time {
  padding-left: .82rem;
  font-size: .62rem;
}
.equipment-calendar-events .equipment-rental-title {
  display: -webkit-box;
  overflow: hidden;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  font-size: .75rem;
  line-height: 1.15;
}
.equipment-time-planner {
  overflow-x: auto;
  background: #fff;
  overscroll-behavior-x: contain;
  scroll-snap-type: x proximity;
}
.equipment-time-planner::-webkit-scrollbar {
  height: 0;
}
.equipment-time-grid {
  --planner-height: 640px;
  display: grid;
  min-width: 100%;
  grid-template-columns: 58px repeat(var(--planner-columns, 7), minmax(132px, 1fr));
  grid-template-rows: 54px var(--planner-height);
  background: #fff;
}
.equipment-time-grid-week {
  min-width: calc(58px + (7 * 132px));
}
.equipment-time-spacer,
.equipment-time-header,
.equipment-time-axis,
.equipment-time-column {
  border-top: 1px solid var(--calendar-line);
}
.equipment-time-spacer {
  border-right: 1px solid var(--calendar-line);
}
.equipment-time-header {
  display: flex;
  min-width: 0;
  align-items: center;
  justify-content: center;
  background: #fbfcff;
  border-left: 1px solid var(--calendar-line);
  padding: .5rem .25rem;
  text-align: center;
  scroll-snap-align: start;
}
.equipment-time-header button {
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  gap: .1rem;
  color: var(--color-secondary-700);
  font-size: .75rem;
  font-weight: 800;
  line-height: 1.1;
}
.equipment-time-header small {
  color: #526782;
  font-size: .62rem;
  font-weight: 700;
  text-transform: uppercase;
}
.equipment-time-axis {
  position: relative;
  border-right: 1px solid var(--calendar-line);
  color: #526782;
  font-size: .72rem;
  font-weight: 700;
}
.equipment-time-axis span {
  position: absolute;
  right: .45rem;
  transform: translateY(-50%);
}
.equipment-time-column {
  position: relative;
  min-width: 0;
  border-left: 1px solid var(--calendar-line);
  background: #fff;
  scroll-snap-align: start;
}
.equipment-time-line {
  position: absolute;
  left: 0;
  right: 0;
  height: 1px;
  background: var(--calendar-line);
  opacity: .72;
}
.equipment-timeline-card {
  position: absolute;
  left: .45rem;
  right: .45rem;
  z-index: 2;
  display: flex;
  min-height: 54px;
  flex-direction: column;
  justify-content: flex-start;
  padding: .65rem .72rem;
}
.equipment-timeline-card .equipment-rental-title {
  line-height: 1.25;
}
.equipment-available-slot {
  position: absolute;
  left: .45rem;
  right: .45rem;
  z-index: 1;
  display: flex;
  min-height: 46px;
  align-items: center;
  justify-content: center;
  border: 1px dashed var(--color-surface-300);
  border-radius: var(--radius);
  background: rgba(255,255,255,.72);
  color: var(--color-secondary-500);
  font-size: .75rem;
  font-weight: 700;
  transition: border-color var(--duration-fast), color var(--duration-fast), background var(--duration-fast);
}
.equipment-available-slot:hover {
  border-color: rgb(var(--theme-primary));
  background: rgb(var(--theme-primary) / .05);
  color: rgb(var(--theme-primary));
}
.equipment-period-legend {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  align-items: center;
  justify-content: center;
  border-top: 1px solid var(--color-surface-200);
  background: #fff;
  padding: .85rem 1rem;
}
.equipment-period-legend span {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  color: var(--color-secondary-600);
  font-size: .8125rem;
  font-weight: 600;
}
.equipment-period-legend i {
  width: 1rem;
  height: 1rem;
  border-radius: .3rem;
  box-shadow: 0 6px 14px rgba(15, 23, 42, .12);
}
.equipment-resource-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: .75rem;
}
.equipment-resource-controls {
  display: grid;
  grid-template-columns: minmax(170px, 240px) auto;
  gap: .75rem;
  align-items: center;
}
.equipment-resource-card {
  position: relative;
  display: flex;
  min-width: 0;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid var(--color-surface-200);
  border-radius: .75rem;
  background: #fff;
  text-align: left;
  box-shadow: 0 14px 34px rgba(15, 23, 42, .055);
  transition: border-color var(--duration-fast), box-shadow var(--duration-fast), transform var(--duration-fast), background var(--duration-fast);
}
.equipment-resource-card:hover,
.equipment-resource-card.is-active {
  border-color: rgb(var(--theme-primary) / .55);
  background: rgb(var(--theme-primary) / .035);
  box-shadow: 0 18px 42px rgb(var(--theme-primary) / .12);
  transform: translateY(-1px);
}
.equipment-resource-card:focus-visible {
  outline: 3px solid rgb(var(--theme-primary) / .2);
  outline-offset: 2px;
}
.equipment-resource-media {
  position: relative;
  display: block;
  aspect-ratio: 4 / 3;
  overflow: hidden;
  background: var(--color-surface-100);
}
.equipment-resource-image {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform .3s ease;
}
.equipment-resource-card:hover .equipment-resource-image {
  transform: scale(1.04);
}
.equipment-resource-empty {
  display: flex;
  width: 100%;
  height: 100%;
  align-items: center;
  justify-content: center;
  background: rgb(var(--theme-primary) / .08);
  color: rgb(var(--theme-primary));
}
.equipment-resource-body {
  display: flex;
  min-height: 6.1rem;
  flex: 1;
  flex-direction: column;
  gap: .45rem;
  padding: .75rem;
}
.equipment-resource-title {
  display: -webkit-box;
  overflow: hidden;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  color: var(--color-secondary-900);
  font-size: .9rem;
  font-weight: 800;
  line-height: 1.2;
}
.equipment-resource-meta {
  overflow: hidden;
  color: var(--color-secondary-500);
  font-size: .72rem;
  font-weight: 700;
  line-height: 1.2;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.equipment-resource-foot {
  margin-top: auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .5rem;
  color: var(--color-secondary-500);
  font-size: .7rem;
  font-weight: 700;
}
.equipment-resource-state {
  display: inline-flex;
  align-items: center;
  gap: .32rem;
  min-width: 0;
}
.equipment-resource-state::before {
  content: "";
  width: .5rem;
  height: .5rem;
  flex: 0 0 auto;
  border-radius: 999px;
  background: var(--resource-status, #16a34a);
}
.equipment-resource-price {
  flex: 0 0 auto;
  color: rgb(var(--theme-primary));
}
.equipment-mobile-count {
  display: none;
}
.equipment-month-dots {
  display: none;
}
.equipment-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  background: rgba(0, 0, 0, .5);
}
.equipment-modal-panel {
  width: min(760px, 100%);
  max-height: 90vh;
  overflow: auto;
  border-radius: 1rem;
  background: #fff;
  box-shadow: var(--shadow-xl);
}
.equipment-modal-panel-wide {
  width: min(980px, 100%);
}
@media (min-width: 640px) {
  .equipment-resource-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
  }
}
@media (min-width: 1180px) {
  .equipment-resource-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}
@media (max-width: 840px) {
  .equipment-resource-controls {
    grid-template-columns: 1fr;
  }
  .equipment-calendar-shell {
    margin-inline: -.25rem;
    border-radius: 1rem;
  }
  .equipment-agenda-toolbar {
    padding: .85rem;
  }
  .equipment-agenda-nav {
    width: 100%;
    justify-content: space-between;
  }
  .equipment-agenda-nav > div {
    min-width: 0;
    text-align: center;
  }
  .equipment-agenda-actions {
    display: grid;
    width: 100%;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .35rem;
  }
  .equipment-view-switch {
    display: contents;
  }
  .equipment-agenda-actions .btn {
    min-width: 0;
    justify-content: center;
    padding-inline: .35rem;
    font-size: .72rem;
  }
  .equipment-calendar-grid {
    grid-template-columns: repeat(7, minmax(0, 1fr));
    border-radius: 0 0 var(--radius-lg) var(--radius-lg);
    overflow: hidden;
  }
  .equipment-calendar-heading {
    display: block;
    padding: .45rem .1rem;
    font-size: .625rem;
  }
  .equipment-calendar-cell {
    min-height: 3.85rem;
    align-items: center;
    justify-content: flex-start;
    gap: .2rem;
    padding: .38rem .18rem;
    position: relative;
    overflow: hidden;
    text-align: center;
  }
  .equipment-day-number {
    margin-bottom: 0;
    color: var(--calendar-text);
  }
  .equipment-calendar-events {
    display: none;
  }
  .equipment-month-dots {
    display: inline-flex;
    max-width: 100%;
    align-items: center;
    justify-content: center;
    gap: .16rem;
    line-height: 1;
  }
  .equipment-month-dots i {
    display: block;
    width: .38rem;
    height: .38rem;
    border-radius: 999px;
    box-shadow: 0 0 0 1px rgba(255,255,255,.9);
  }
  .equipment-month-dots b {
    color: rgb(var(--theme-primary));
    font-size: .58rem;
    font-weight: 800;
  }
  .equipment-calendar-cell-muted .equipment-day-number {
    color: #a6b0bf;
  }
  .equipment-period-legend {
    gap: .75rem;
    justify-content: space-around;
    padding: .75rem .5rem;
  }
  .equipment-period-legend span {
    font-size: .75rem;
  }
  .equipment-mobile-count {
    display: none;
    min-width: 1.25rem;
    height: 1.25rem;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: rgb(var(--theme-primary) / .1);
    color: rgb(var(--theme-primary));
    font-size: .6875rem;
	    font-weight: 700;
	  }
	  .equipment-time-grid {
	    --planner-height: 720px;
	    grid-template-columns: 48px repeat(var(--planner-columns, 7), minmax(118px, 1fr));
	    grid-template-rows: 48px var(--planner-height);
	  }
	  .equipment-time-grid-week {
	    min-width: calc(48px + (7 * 118px));
	  }
	  .equipment-time-grid:not(.equipment-time-grid-week) {
	    min-width: 100%;
	    grid-template-columns: 48px minmax(270px, 1fr);
	  }
	  .equipment-time-spacer,
	  .equipment-time-axis {
	    position: sticky;
	    left: 0;
	    z-index: 5;
	    background: #fff;
	  }
	  .equipment-time-header {
	    position: sticky;
	    top: 0;
	    z-index: 4;
	    background: #fbfcff;
	  }
	  .equipment-time-header {
	    padding: .35rem .1rem;
	  }
	  .equipment-time-header button {
	    font-size: .66rem;
	  }
	  .equipment-time-header small {
	    font-size: .56rem;
	  }
	  .equipment-time-axis {
	    font-size: .66rem;
	  }
	  .equipment-time-axis span {
	    right: .25rem;
	  }
	  .equipment-timeline-card {
	    left: .36rem;
	    right: .36rem;
	    min-height: 44px;
	    border-radius: .72rem;
	    padding: .52rem .5rem .52rem .58rem;
	  }
	  .equipment-timeline-card::before {
	    left: .42rem;
	    top: .48rem;
	    width: .42rem;
	    height: .42rem;
	  }
	  .equipment-timeline-card .equipment-rental-time {
	    padding-left: .72rem;
	    font-size: .58rem;
	  }
	  .equipment-timeline-card .equipment-rental-title {
	    font-size: .72rem;
	    line-height: 1.16;
	  }
	  .equipment-timeline-card .equipment-rental-details {
	    display: none;
	  }
	  .equipment-available-slot {
	    left: .36rem;
	    right: .36rem;
	    font-size: .62rem;
	    text-align: center;
	  }
	}
`;

function fallbackEquipmentData(){
  var permissions=[
    "reservations.view",
    "reservations.create",
    "reservations.update_own",
    "reservations.update_any",
    "reservations.delete_own",
    "reservations.delete_any",
    "reservations.manage_vehicles",
    "equipment_rentals.view",
    "equipment_rentals.create",
    "equipment_rentals.update_own",
    "equipment_rentals.update_any",
    "equipment_rentals.delete_own",
    "equipment_rentals.delete_any",
    "equipment_rentals.manage_items",
    "platform.manage_sites",
    "platform.manage_users",
    "platform.manage_roles",
    "platform.manage_modules"
  ];
  var sites=[
    {id:1,name:"Palissy",slug:"palissy",hours:{morningStart:"07:30",morningEnd:"12:00",afternoonStart:"13:30",afternoonEnd:"17:30"}},
    {id:2,name:"Annexe",slug:"annexe",hours:{morningStart:"07:30",morningEnd:"12:00",afternoonStart:"13:30",afternoonEnd:"17:30"}},
    {id:3,name:"Pessac",slug:"pessac",hours:{morningStart:"07:30",morningEnd:"12:00",afternoonStart:"13:30",afternoonEnd:"17:30"}},
    {id:4,name:"Glotin",slug:"glotin",hours:{morningStart:"07:30",morningEnd:"12:00",afternoonStart:"13:30",afternoonEnd:"17:30"}},
    {id:5,name:"Pastel",slug:"pastel",hours:{morningStart:"07:30",morningEnd:"12:00",afternoonStart:"13:30",afternoonEnd:"17:30"}}
  ];
  var modules=[
    {id:7,name:"Location materiel",slug:"locations-materiel",description:"Planning et locations du materiel interne",active:true,sortOrder:15}
  ];
  var users=[
    {id:1,name:"J-Philippe",role:"admin",active:true,siteIds:[1,2,3,4,5],moduleIds:[7],permissions:permissions},
    {id:2,name:"Christophe L",role:"responsable",active:true,siteIds:[1,2,3],moduleIds:[7],permissions:permissions},
    {id:3,name:"Remi G",role:"user",active:true,siteIds:[1],moduleIds:[7],permissions:permissions}
  ];
  var equipmentItems=[
    {id:1,siteId:1,categoryId:1,name:"Ponceuse parquet",inventoryCode:"PON-PARQUET",description:"Ponceuse principale pour parquet",color:"#95002e",photoUrl:"",halfDayPrice:45,dayPrice:80,showDayPrice:true,depositAmount:300,active:true,sortOrder:10},
    {id:2,siteId:1,categoryId:1,name:"Bordureuse",inventoryCode:"BOR-001",description:"Bordureuse pour finitions et plinthes",color:"#f59e0b",photoUrl:"",halfDayPrice:35,dayPrice:60,showDayPrice:true,depositAmount:200,active:true,sortOrder:20},
    {id:3,siteId:1,categoryId:1,name:"Aspirateur chantier",inventoryCode:"ASP-001",description:"Aspirateur poussiere pour poncage",color:"#1d354f",photoUrl:"",halfDayPrice:25,dayPrice:40,showDayPrice:true,depositAmount:150,active:true,sortOrder:30}
  ];
  return{
    ok:true,
    mode:"local-fallback",
    source:"fallback",
    user:users[0],
    sites:sites,
    modules:modules,
    equipmentCategories:[{id:1,name:"Poncage",slug:"poncage",active:true,sortOrder:10}],
    equipmentItems:equipmentItems,
    equipmentRentals:[],
    permissions:permissions.map(function(name){return{name:name,label:name,group:"Location materiel"}}),
    users:users
  };
}

function EquipmentRentalsPage(){
  var mountRef=React.useRef(null);

  React.useEffect(function(){
    var root=mountRef.current;
    if(!root)return;

    var disposed=false;
    var API="/api/equipment-rentals.php";
    var dateFormatter=new Intl.DateTimeFormat("fr-FR",{dateStyle:"medium",timeStyle:"short"});
    var dayFormatter=new Intl.DateTimeFormat("fr-FR",{weekday:"short",day:"2-digit",month:"2-digit"});
    var monthFormatter=new Intl.DateTimeFormat("fr-FR",{month:"long",year:"numeric"});
    var topSiteHostId="equipment-site-topbar-host";
    var globalSiteStorageKey="crm:active-site-id";
    var globalSiteEventName="crm:active-site-changed";
    var now=new Date();
    var fallbackData=fallbackEquipmentData();
    var state={
      data:fallbackData,
      error:"",
      loading:false,
      saving:false,
      notice:null,
      selectedUserId:fallbackData.user.id,
      selectedSiteId:fallbackData.user.siteIds[0]||fallbackData.sites[0]?.id||null,
      selectedItemId:null,
      view:"month",
      focusDate:new Date(now.getFullYear(),now.getMonth(),now.getDate()),
      modal:null,
      itemModal:null,
      adminOpen:false,
      viewAll:false,
      itemCategoryId:""
    };

    function esc(value){
      return String(value??"")
        .replace(/&/g,"&amp;")
        .replace(/</g,"&lt;")
        .replace(/>/g,"&gt;")
        .replace(/"/g,"&quot;")
        .replace(/'/g,"&#039;");
    }

    function dateKey(date){
      var month=String(date.getMonth()+1).padStart(2,"0");
      var day=String(date.getDate()).padStart(2,"0");
      return `${date.getFullYear()}-${month}-${day}`;
    }

    function parseDate(value){
      return new Date(String(value).replace(" ","T"));
    }

    function dateTimeLocal(date){
      var month=String(date.getMonth()+1).padStart(2,"0");
      var day=String(date.getDate()).padStart(2,"0");
      var hours=String(date.getHours()).padStart(2,"0");
      var minutes=String(date.getMinutes()).padStart(2,"0");
      return `${date.getFullYear()}-${month}-${day}T${hours}:${minutes}`;
    }

    function addDays(date,amount){
      var next=new Date(date);
      next.setDate(next.getDate()+amount);
      return next;
    }

    function startOfWeek(date){
      var next=new Date(date);
      next.setHours(0,0,0,0);
      next.setDate(next.getDate()-((next.getDay()+6)%7));
      return next;
    }

    function monthDays(date){
      var start=startOfWeek(new Date(date.getFullYear(),date.getMonth(),1));
      return Array.from({length:42},function(_,index){return addDays(start,index)});
    }

    function overlaps(startA,endA,startB,endB){
      return parseDate(startA)<parseDate(endB)&&parseDate(endA)>parseDate(startB);
    }

    function actorHeaders(){
      return state.selectedUserId?{"X-CRM-User-Id":String(state.selectedUserId)}:{};
    }

    function removeTopSiteSelector(){
      document.getElementById(topSiteHostId)?.remove();
    }

    async function api(action,body){
      var response=await fetch(`${API}?action=${action}`,{
        method:body?"POST":"GET",
        credentials:"same-origin",
        headers:body?{"Content-Type":"application/json",...actorHeaders()}:actorHeaders(),
        body:body?JSON.stringify(body):void 0
      });
      var payload=await response.json();
      if(!response.ok||payload.ok===false)throw new Error(payload.error||"Action refusee");
      return payload;
    }

    async function loadData(preferredUserId,options){
      var keepCurrent=!!(options&&options.keepCurrent);
      if(!keepCurrent){
        state.loading=true;
        render();
      }
      try{
        var params=new URLSearchParams({action:"bootstrap"});
        if(preferredUserId)params.set("user_id",String(preferredUserId));
        var headers=preferredUserId?{"X-CRM-User-Id":String(preferredUserId)}:{};
        var response=await fetch(`${API}?${params.toString()}`,{headers,credentials:"same-origin"});
        var payload=await response.json();
        if(!response.ok||payload.ok===false)throw new Error(payload.error||"API indisponible");
        if(disposed)return;
        state.data=payload;
        state.error="";
        state.selectedUserId=payload.user.id;
        var allowedSites=payload.user.siteIds||[];
        var globalSite=globalSiteId();
        state.selectedSiteId=allowedSites.includes(globalSite)?globalSite:(allowedSites.includes(state.selectedSiteId)?state.selectedSiteId:(allowedSites[0]||payload.sites[0]?.id||null));
        if(state.selectedItemId&&!payload.equipmentItems.some(function(item){return item.id===state.selectedItemId&&item.siteId===state.selectedSiteId&&item.active})){
          state.selectedItemId=null;
        }
        clearUnavailableResourceFilters();
        clearSelectedItemIfHidden();
      }catch(error){
        if(disposed)return;
        if(keepCurrent&&state.data){
          state.notice={type:"error",message:`Mode local : ${error instanceof Error?error.message:"API location materiel indisponible"}`};
        }else{
          state.error=error instanceof Error?error.message:"API location materiel indisponible";
        }
      }finally{
        if(!disposed){
          state.loading=false;
          render();
        }
      }
    }

    function activeUser(){
      if(!state.data)return null;
      return state.data.users.find(function(user){return user.id===state.selectedUserId})||state.data.user;
    }

    function globalSiteId(){
      var fromBridge=Number(window.CRM_ACTIVE_SITE?.getSiteId?.()||0);
      var fromStorage=Number(window.localStorage.getItem(globalSiteStorageKey)||0);
      return fromBridge||fromStorage||null;
    }

    function applyGlobalSite(siteId){
      var next=Number(siteId||globalSiteId()||0);
      var allowedSites=activeUser()?.siteIds||[];
      if(!next||!allowedSites.includes(next)||state.selectedSiteId===next)return;
      state.selectedSiteId=next;
      state.selectedItemId=null;
      state.itemCategoryId="";
      state.viewAll=false;
      render();
    }

    function activeSite(){
      if(!state.data)return null;
      return state.data.sites.find(function(site){return site.id===state.selectedSiteId})||state.data.sites[0]||null;
    }

    function siteHours(site){
      var hours=site?.hours||{};
      return{
        morningStart:String(hours.morningStart||site?.morningStart||"07:30").slice(0,5),
        morningEnd:String(hours.morningEnd||site?.morningEnd||"12:00").slice(0,5),
        afternoonStart:String(hours.afternoonStart||site?.afternoonStart||"13:30").slice(0,5),
        afternoonEnd:String(hours.afternoonEnd||site?.afternoonEnd||"17:30").slice(0,5)
      };
    }

    function siteHoursLabel(site){
      var hours=siteHours(site);
      return`${hours.morningStart}-${hours.morningEnd} / ${hours.afternoonStart}-${hours.afternoonEnd}`;
    }

    function applyTime(day,time){
      var date=new Date(day);
      var parts=String(time||"00:00").split(":").map(Number);
      date.setHours(parts[0]||0,parts[1]||0,0,0);
      return date;
    }

    function timeMinutes(time){
      var parts=String(time||"00:00").slice(0,5).split(":").map(Number);
      return(parts[0]||0)*60+(parts[1]||0);
    }

    function rentalPeriodTone(rental){
      var hours=siteHours(activeSite());
      var start=timeMinutes(rental.startAt.slice(11,16));
      var end=timeMinutes(rental.endAt.slice(11,16));
      var morningStart=timeMinutes(hours.morningStart);
      var morningEnd=timeMinutes(hours.morningEnd);
      var afternoonStart=timeMinutes(hours.afternoonStart);
      var afternoonEnd=timeMinutes(hours.afternoonEnd);
	      if(start<=morningStart+15&&end>=afternoonEnd-15){
	        return{
	          name:"day",
	          bg:"linear-gradient(135deg,var(--period-day-a) 0%,var(--period-day-b) 100%)",
	          border:"var(--period-day-border)",
	          accent:"var(--period-day-accent)",
	          text:"#ffffff",
	          muted:"#eef3ff"
	        };
	      }
	      if(end<=morningEnd+15){
	        return{
	          name:"morning",
	          bg:"linear-gradient(135deg,var(--period-morning-a) 0%,var(--period-morning-b) 100%)",
	          border:"var(--period-morning-border)",
	          accent:"var(--period-morning-accent)",
	          text:"var(--period-morning-text)",
	          muted:"#08797d"
	        };
	      }
	      if(start>=morningEnd-15||start>=afternoonStart-30){
	        return{
	          name:"afternoon",
	          bg:"linear-gradient(135deg,var(--period-afternoon-a) 0%,var(--period-afternoon-b) 100%)",
	          border:"var(--period-afternoon-border)",
	          accent:"var(--period-afternoon-accent)",
	          text:"var(--period-afternoon-text)",
	          muted:"#a62d2b"
	        };
	      }
      return{
        name:"mixed",
        bg:"linear-gradient(135deg,#f8fafc 0%,#e9edf3 100%)",
        border:"#b9c3cf",
        accent:"#64748b",
        text:"#1e293b",
        muted:"#526174"
      };
    }

    function activeRole(){
      var user=activeUser();
      if(!user||!state.data)return{blocked:true,permissions:[]};
      var module=state.data.modules.find(function(item){return item.slug==="locations-materiel"});
      var moduleAllowed=!module||user.moduleIds.includes(module.id);
      return{
        blocked:!moduleAllowed||!user.permissions.includes("equipment_rentals.view"),
        permissions:user.permissions
      };
    }

    function hasPermission(name){
      return activeRole().permissions.includes(name);
    }

    function canUpdate(rental){
      var user=activeUser();
      return hasPermission("equipment_rentals.update_any")||(hasPermission("equipment_rentals.update_own")&&rental.userId===user?.id);
    }

    function canDelete(rental){
      var user=activeUser();
      return hasPermission("equipment_rentals.delete_any")||(hasPermission("equipment_rentals.delete_own")&&rental.userId===user?.id);
    }

    function initials(value){
      return String(value||"?").trim().split(/\s+/).slice(0,2).map(function(part){return part.charAt(0)}).join("").toUpperCase()||"?";
    }

    function imageStyle(sizeClass){
      var sizes={14:"3.5rem",16:"4rem",20:"5rem",28:"7rem"};
      var height=String(sizeClass||"").match(/h-(\d+)/);
      var width=String(sizeClass||"").match(/w-(\d+)/);
      var h=sizes[height?.[1]];
      var w=sizes[width?.[1]];
      return h&&w?` style="height:${h};width:${w}"`:"";
    }

    function imageHtml(url,label,sizeClass){
      var size=sizeClass||"h-14 w-14";
      if(url){
        return`<img src="${esc(url)}" alt="${esc(label)}" class="${size} shrink-0 rounded-lg object-cover"${imageStyle(size)}>`;
      }
      return`<div class="${size} shrink-0 rounded-lg bg-theme-primary/10 text-theme-primary flex items-center justify-center text-ui-xs font-bold"${imageStyle(size)}>${esc(initials(label))}</div>`;
    }

    function productImageHtml(url,label){
      if(url){
        return`<img src="${esc(url)}" alt="${esc(label)}" class="equipment-resource-image">`;
      }
      return`<div class="equipment-resource-empty">
        <span class="text-lg font-bold">${esc(initials(label))}</span>
      </div>`;
    }

    function allItemsForSite(){
      if(!state.data||!state.selectedSiteId)return[];
      return state.data.equipmentItems
        .filter(function(item){return item.siteId===state.selectedSiteId&&item.active})
        .sort(function(a,b){return(a.sortOrder-b.sortOrder)||a.name.localeCompare(b.name)});
    }

    function categoriesForSite(){
      if(!state.data)return[];
      var ids=new Set(allItemsForSite().map(function(item){return Number(item.categoryId||0)}).filter(Boolean));
      return state.data.equipmentCategories
        .filter(function(category){return ids.has(Number(category.id))})
        .sort(function(a,b){return(a.sortOrder-b.sortOrder)||a.name.localeCompare(b.name)});
    }

    function filteredItemsForSite(){
      var categoryId=Number(state.itemCategoryId||0);
      return allItemsForSite().filter(function(item){
        if(categoryId&&Number(item.categoryId)!==categoryId)return false;
        return true;
      });
    }

    function clearUnavailableResourceFilters(){
      if(!state.itemCategoryId)return;
      var categoryId=Number(state.itemCategoryId||0);
      if(!categoriesForSite().some(function(category){return Number(category.id)===categoryId})){
        state.itemCategoryId="";
      }
    }

    function clearSelectedItemIfHidden(){
      if(!state.selectedItemId)return;
      if(!filteredItemsForSite().some(function(item){return item.id===state.selectedItemId})){
        state.selectedItemId=null;
      }
    }

    function selectedItem(){
      if(!state.selectedItemId)return null;
      return allItemsForSite().find(function(item){return item.id===state.selectedItemId})||null;
    }

    function itemsForSite(){
      var items=allItemsForSite();
      if(!state.selectedItemId)return items;
      return items.filter(function(item){return item.id===state.selectedItemId});
    }

    function rentalsForSite(){
      if(!state.data||!state.selectedSiteId)return[];
      return state.data.equipmentRentals.filter(function(rental){
        return rental.siteId===state.selectedSiteId&&(!state.selectedItemId||rental.equipmentItemId===state.selectedItemId);
      });
    }

    function rentalsForDay(day){
      var key=dateKey(day);
      return rentalsForSite()
        .filter(function(rental){return rental.startAt.slice(0,10)<=key&&rental.endAt.slice(0,10)>=key})
        .sort(function(a,b){return a.startAt.localeCompare(b.startAt)});
    }

    function itemById(id){
      return state.data?.equipmentItems.find(function(item){return item.id===Number(id)})||null;
    }

    function categoryById(id){
      return state.data?.equipmentCategories.find(function(category){return category.id===Number(id)})||null;
    }

    function slotRange(day,slot){
      var hours=siteHours(activeSite());
      if(slot==="afternoon"){
        return[dateTimeLocal(applyTime(day,hours.afternoonStart)),dateTimeLocal(applyTime(day,hours.afternoonEnd))];
      }
      if(slot==="full_day"){
        return[dateTimeLocal(applyTime(day,hours.morningStart)),dateTimeLocal(applyTime(day,hours.afternoonEnd))];
      }
      return[dateTimeLocal(applyTime(day,hours.morningStart)),dateTimeLocal(applyTime(day,hours.morningEnd))];
    }

    function firstAvailableItem(startAt,endAt,ignoreRentalId){
      return itemsForSite().find(function(item){
        return!rentalsForSite().some(function(rental){
          if(rental.status==="cancelled"||rental.id===ignoreRentalId)return false;
          return rental.equipmentItemId===item.id&&overlaps(startAt,endAt,rental.startAt,rental.endAt);
        });
      });
    }

    function upcomingRentals(){
      var nowKey=dateTimeLocal(new Date());
      return rentalsForSite()
        .filter(function(rental){return rental.status!=="cancelled"&&rental.endAt>=nowKey})
        .sort(function(a,b){return a.startAt.localeCompare(b.startAt)});
    }

    function itemAvailableNow(item){
      var nowKey=dateTimeLocal(new Date());
      return!state.data.equipmentRentals.some(function(rental){
        return rental.siteId===state.selectedSiteId&&
          rental.equipmentItemId===item.id&&
          rental.status!=="cancelled"&&
          rental.startAt<=nowKey&&
          rental.endAt>nowKey;
      });
    }

    function availabilityBadgeHtml(available){
      var label=available?"Disponible":"Indisponible";
      return`<span class="absolute h-3 w-3 rounded-full border-2 border-white shadow-sm ring-1 ring-black/10" style="right:8px;top:8px;z-index:2;background:${available?"#16a34a":"#dc2626"}" aria-label="${label}" title="${label}"></span>`;
    }

	    function rentalCard(rental,compact){
	      var item=itemById(rental.equipmentItemId);
	      var tone=rentalPeriodTone(rental);
	      var style=`--rental-bg:${tone.bg};--rental-border:${tone.border};--rental-accent:${tone.accent};--rental-text:${tone.text};--rental-muted:${tone.muted};`;
      var itemName=item?.name||rental.title||"Materiel";
      var details=[];
      if(!compact&&rental.title&&rental.title!==itemName)details.push(rental.title);
      if(!compact&&rental.userName)details.push(rental.userName);
      var editAttr=canUpdate(rental)?` data-edit="${rental.id}"`:"";
      var deleteButton=canDelete(rental)?`<button type="button" class="btn btn-ghost btn-xs" data-delete="${rental.id}" aria-label="Supprimer">Supprimer</button>`:"";
      return`
        <article class="equipment-rental-card" data-period="${tone.name}"${editAttr} style="${style}">
          <div class="flex items-center justify-between gap-2">
            <span class="equipment-rental-time text-ui-xs font-bold">${esc(rental.startAt.slice(11,16))}-${esc(rental.endAt.slice(11,16))}</span>
          </div>
          <p class="equipment-rental-title mt-1 text-label font-bold">${esc(itemName)}</p>
          ${compact||!details.length?"":`<p class="equipment-rental-details mt-1 text-caption">${esc(details.join(" - "))}</p>`}
          ${compact?"":`<div class="mt-2 flex flex-wrap items-center gap-2">${deleteButton}</div>`}
	        </article>
	      `;
	    }

	    function minuteLabel(minutes){
	      var hours=String(Math.floor(minutes/60)).padStart(2,"0");
	      var mins=String(minutes%60).padStart(2,"0");
	      return`${hours}:${mins}`;
	    }

	    function plannerBounds(){
	      var hours=siteHours(activeSite());
	      var start=Math.floor(Math.min(timeMinutes(hours.morningStart),timeMinutes(hours.afternoonStart))/60)*60;
	      var end=Math.ceil(Math.max(timeMinutes(hours.morningEnd),timeMinutes(hours.afternoonEnd))/60)*60;
	      if(end<=start)end=start+10*60;
	      var marks=[];
	      for(var minute=start;minute<=end;minute+=60)marks.push(minute);
	      if(marks[marks.length-1]!==end)marks.push(end);
	      return{start:start,end:end,total:end-start,marks:marks};
	    }

	    function timelinePosition(startAt,endAt,day,bounds){
	      var key=dateKey(day);
	      var start=startAt.slice(0,10)<key?bounds.start:timeMinutes(startAt.slice(11,16));
	      var end=endAt.slice(0,10)>key?bounds.end:timeMinutes(endAt.slice(11,16));
	      start=Math.max(bounds.start,Math.min(bounds.end,start));
	      end=Math.max(bounds.start,Math.min(bounds.end,end));
	      if(end<=start)end=Math.min(bounds.end,start+30);
	      var top=((start-bounds.start)/bounds.total)*100;
	      var height=Math.max(((end-start)/bounds.total)*100,7);
	      if(top+height>100)top=Math.max(0,100-height);
	      return{top:top,height:height};
	    }

	    function timeLinesHtml(bounds){
	      return bounds.marks.map(function(mark){
	        var top=((mark-bounds.start)/bounds.total)*100;
	        return`<span class="equipment-time-line" style="top:${top}%"></span>`;
	      }).join("");
	    }

	    function timeAxisHtml(bounds){
	      return`<div class="equipment-time-axis">
	        ${bounds.marks.map(function(mark){
	          var top=((mark-bounds.start)/bounds.total)*100;
	          return`<span style="top:${top}%">${minuteLabel(mark)}</span>`;
	        }).join("")}
	      </div>`;
	    }

	    function timelineRentalCard(rental,day){
	      var item=itemById(rental.equipmentItemId);
	      var tone=rentalPeriodTone(rental);
	      var pos=timelinePosition(rental.startAt,rental.endAt,day,plannerBounds());
	      var itemName=item?.name||rental.title||"Materiel";
	      var details=[];
	      if(rental.title&&rental.title!==itemName)details.push(rental.title);
	      if(rental.userName)details.push(rental.userName);
	      var editAttr=canUpdate(rental)?` data-edit="${rental.id}"`:"";
	      var style=`--rental-bg:${tone.bg};--rental-border:${tone.border};--rental-accent:${tone.accent};--rental-text:${tone.text};--rental-muted:${tone.muted};top:${pos.top}%;height:${pos.height}%;`;
	      return`
	        <article class="equipment-rental-card equipment-timeline-card" data-period="${tone.name}"${editAttr} style="${style}">
	          <span class="equipment-rental-time text-ui-xs font-bold">${esc(rental.startAt.slice(11,16))}-${esc(rental.endAt.slice(11,16))}</span>
	          <p class="equipment-rental-title mt-1 text-label font-bold">${esc(itemName)}</p>
	          ${details.length?`<p class="equipment-rental-details mt-1 text-caption">${esc(details.join(" - "))}</p>`:""}
	        </article>
	      `;
	    }

	    function availableSlotButton(slot,startAt,endAt,bounds){
	      var available=firstAvailableItem(startAt,endAt);
	      if(!available||!hasPermission("equipment_rentals.create"))return"";
	      var pos=timelinePosition(startAt,endAt,state.focusDate,bounds);
	      return`<button type="button" class="equipment-available-slot" data-slot="${slot}" style="top:${pos.top}%;height:${pos.height}%">Creneau disponible</button>`;
	    }

	    function periodLegendHtml(){
	      return`<div class="equipment-period-legend">
        <span><i style="background:linear-gradient(135deg,#55cbc5 0%,#10aaa4 100%)"></i>Matin</span>
        <span><i style="background:linear-gradient(135deg,#ff8b7d 0%,#ff5e52 100%)"></i>Apres-midi</span>
        <span><i style="background:linear-gradient(135deg,#5d7cff 0%,#3f5ee6 100%)"></i>Journee complete</span>
      </div>`;
	    }

    function statCards(){
      var site=activeSite();
      var items=itemsForSite();
      var rentals=rentalsForSite();
      var selected=selectedItem();
      var currentMonth=dateKey(state.focusDate).slice(0,7);
      var monthCount=rentals.filter(function(rental){return rental.startAt.startsWith(currentMonth)}).length;
      var nowStart=dateTimeLocal(new Date());
      var nowEnd=dateTimeLocal(new Date(Date.now()+4*60*60*1000));
      var available=items.filter(function(item){
        return!rentals.some(function(rental){
          return rental.status!=="cancelled"&&rental.equipmentItemId===item.id&&overlaps(nowStart,nowEnd,rental.startAt,rental.endAt);
        });
      });
      var next=upcomingRentals()[0];
      var cards=[
        ["Site actif",site?.name||"-",siteHoursLabel(site)],
        ["Disponibles",`${available.length}/${items.length}`,selected?selected.name:"Materiel du site"],
        ["Locations du mois",String(monthCount),monthFormatter.format(state.focusDate)],
        ["Prochaine location",next?next.startAt.slice(11,16):"Aucune",next?dateFormatter.format(parseDate(next.startAt)):"Planning libre"]
      ];
      return`<section class="grid grid-cols-2 gap-4 md:grid-cols-2 xl:grid-cols-4">
        ${cards.map(function(card){return`
          <div class="card rounded-xl p-4">
            <p class="text-caption text-secondary-500">${esc(card[0])}</p>
            <p class="heading-4 mt-1 text-secondary-900">${esc(card[1])}</p>
            <p class="text-ui-xs text-secondary-400">${esc(card[2])}</p>
            ${card[0]==="Prochaine location"?'<button type="button" class="mt-3 text-body-sm font-semibold text-theme-primary" data-view-all>Voir tout</button>':""}
          </div>
        `}).join("")}
      </section>`;
    }

    function resourcesSection(){
      var allItems=allItemsForSite();
      var items=filteredItemsForSite();
      var categories=categoriesForSite();
      var selected=selectedItem();
      var filtersActive=!!state.itemCategoryId;
      return`
        <section class="space-y-4">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h2 class="heading-5 text-secondary-900">Materiel du site</h2>
              <p class="text-body-sm text-secondary-500">${selected?`Planning filtre sur ${esc(selected.name)}`:"Selectionnez un materiel pour afficher son planning"}</p>
            </div>
            <div class="flex items-center gap-3">
              <span class="badge badge-primary">${items.length}/${allItems.length}</span>
              ${selected?'<button type="button" class="text-body-sm font-semibold text-theme-primary" data-item-filter="">Changer</button>':""}
            </div>
          </div>
          <div class="equipment-resource-controls">
            <select class="select-native input" data-item-category-filter aria-label="Categorie">
              <option value="">Toutes categories</option>
              ${categories.map(function(category){return`<option value="${category.id}" ${String(category.id)===String(state.itemCategoryId)?"selected":""}>${esc(category.name)}</option>`}).join("")}
            </select>
            ${filtersActive?'<button type="button" class="btn btn-secondary btn-sm" data-item-filter-clear>Effacer</button>':""}
          </div>
          <div class="equipment-resource-grid">
            ${items.map(function(item){
              var active=selected?.id===item.id;
              var available=itemAvailableNow(item);
              var category=categoryById(item.categoryId);
              return`<button type="button" class="equipment-resource-card${active?" is-active":""}" data-item-filter="${item.id}" aria-pressed="${active?"true":"false"}">
                <span class="equipment-resource-media">
                  ${productImageHtml(item.photoUrl,item.name)}
                  ${availabilityBadgeHtml(available)}
                </span>
                <span class="equipment-resource-body">
                  <span class="equipment-resource-title">${esc(item.name)}</span>
                  <span class="equipment-resource-meta">${esc(category?.name||item.inventoryCode||"Materiel")}</span>
                  <span class="equipment-resource-foot">
                    <span class="equipment-resource-state" style="--resource-status:${available?"#16a34a":"#dc2626"}">${available?"Disponible":"Indisponible"}</span>
                    ${item.showDayPrice===false?"":`<span class="equipment-resource-price">${Number(item.dayPrice||0).toFixed(0)} EUR/j</span>`}
                  </span>
                </span>
              </button>`;
            }).join("")||`<p class="w-full rounded-xl border border-dashed border-surface-200 p-4 text-center text-body-sm text-secondary-400">${allItems.length?"Aucun materiel ne correspond aux filtres.":"Aucun materiel sur ce site."}</p>`}
          </div>
        </section>
      `;
    }

    function calendarHeader(){
      var step=state.view==="month"?30:state.view==="week"?7:1;
      var selected=selectedItem();
      return`
        <div class="equipment-agenda-toolbar flex flex-col gap-3 border-b border-surface-200 p-4 sm:flex-row sm:items-center sm:justify-between">
          <div class="equipment-agenda-nav flex items-center gap-2">
            <button type="button" class="btn btn-secondary btn-sm btn-icon" data-move="${-step}" aria-label="Periode precedente">&lt;</button>
            <div>
              <p class="heading-5 text-secondary-900">${esc(state.view==="month"?monthFormatter.format(state.focusDate):dayFormatter.format(state.focusDate))}</p>
              <p class="text-caption text-secondary-500">${selected?`Planning ${esc(selected.name)}`:"Planning location materiel"}</p>
            </div>
            <button type="button" class="btn btn-secondary btn-sm btn-icon" data-move="${step}" aria-label="Periode suivante">&gt;</button>
          </div>
          <div class="equipment-agenda-actions flex flex-wrap items-center gap-2">
            <span class="equipment-view-switch">${["month","week","day"].map(function(view){return`<button type="button" class="btn btn-${state.view===view?"primary":"secondary"} btn-sm" data-view="${view}">${view==="month"?"Mois":view==="week"?"Semaine":"Jour"}</button>`}).join("")}</span>
            <button type="button" class="btn btn-ghost btn-sm" data-today>Aujourd'hui</button>
          </div>
        </div>
      `;
    }

    function renderMonth(){
      var month=state.focusDate.getMonth();
      return`<div class="equipment-calendar-grid">
        ${["Lun","Mar","Mer","Jeu","Ven","Sam","Dim"].map(function(label){return`<div class="equipment-calendar-heading bg-surface-50 px-2 py-2 text-center text-ui-xs font-bold uppercase text-secondary-500">${label}</div>`}).join("")}
        ${monthDays(state.focusDate).map(function(day){
          var rentals=rentalsForDay(day);
          var muted=day.getMonth()!==month?" equipment-calendar-cell-muted":"";
          return`<button type="button" class="equipment-calendar-cell${muted}" data-day="${dateKey(day)}">
            <span class="equipment-day-number mb-2 block text-ui-xs font-bold text-secondary-600">${day.getDate()}</span>
            ${rentals.length?`<span class="equipment-month-dots">${rentals.slice(0,3).map(function(rental){var dotTone=rentalPeriodTone(rental);return`<i style="background:${dotTone.accent}"></i>`}).join("")}${rentals.length>3?`<b>+${rentals.length-3}</b>`:""}</span>`:""}
            <div class="equipment-calendar-events space-y-1.5">
              ${rentals.slice(0,2).map(function(rental){return rentalCard(rental,true)}).join("")}
              ${rentals.length>2?`<span class="text-ui-xs font-semibold text-theme-primary">+${rentals.length-2} autres</span>`:""}
            </div>
            ${rentals.length?`<span class="equipment-mobile-count">${rentals.length>9?"9+":rentals.length}</span>`:""}
          </button>`;
        }).join("")}
      </div>`;
    }

	    function renderWeek(){
	      var start=startOfWeek(state.focusDate);
	      var days=Array.from({length:7},function(_,index){return addDays(start,index)});
	      var bounds=plannerBounds();
	      return`<div class="equipment-time-planner rounded-b-xl">
	        <div class="equipment-time-grid equipment-time-grid-week" style="--planner-columns:7">
	          <div class="equipment-time-spacer"></div>
	          ${days.map(function(day){
	            var label=dayFormatter.format(day).replace(". ","<br>");
	            return`<div class="equipment-time-header"><button type="button" data-day="${dateKey(day)}">${label}</button></div>`;
	          }).join("")}
	          ${timeAxisHtml(bounds)}
	          ${days.map(function(day){
	            var rentals=rentalsForDay(day);
	            return`<div class="equipment-time-column">
	              ${timeLinesHtml(bounds)}
	              ${rentals.map(function(rental){return timelineRentalCard(rental,day)}).join("")}
	            </div>`;
	          }).join("")}
	        </div>
	      </div>`;
	    }

	    function renderDay(){
	      var hours=siteHours(activeSite());
	      var bounds=plannerBounds();
	      var rentals=rentalsForDay(state.focusDate);
	      var morning=slotRange(state.focusDate,"morning");
	      var afternoon=slotRange(state.focusDate,"afternoon");
	      return`<div class="equipment-time-planner rounded-b-xl">
	        <div class="equipment-time-grid" style="--planner-columns:1">
	          <div class="equipment-time-spacer"></div>
	          <div class="equipment-time-header"><button type="button" data-day="${dateKey(state.focusDate)}">${esc(dayFormatter.format(state.focusDate))}<small>${esc(hours.morningStart)}-${esc(hours.afternoonEnd)}</small></button></div>
	          ${timeAxisHtml(bounds)}
	          <div class="equipment-time-column">
	            ${timeLinesHtml(bounds)}
	            ${rentals.map(function(rental){return timelineRentalCard(rental,state.focusDate)}).join("")}
	            ${availableSlotButton("morning",morning[0],morning[1],bounds)}
	            ${availableSlotButton("afternoon",afternoon[0],afternoon[1],bounds)}
	          </div>
	        </div>
	      </div>`;
	    }

	    function calendarSection(){
	      return`<section class="card equipment-calendar-shell overflow-hidden rounded-xl" data-equipment-calendar>
        ${calendarHeader()}
        ${state.view==="month"?renderMonth():state.view==="week"?renderWeek():renderDay()}
        ${periodLegendHtml()}
      </section>`;
    }

    function scrollCalendarIntoView(){
      var calendar=root.querySelector("[data-equipment-calendar]");
      if(!calendar)return;
      var header=document.querySelector(".layout-header");
      var offset=(header?.getBoundingClientRect().height||0)+16;
      var scroller=calendar.parentElement;
      var top;
      while(scroller&&scroller!==document.body){
        var style=window.getComputedStyle(scroller);
        var canScroll=/auto|scroll|overlay/.test(style.overflowY);
        if(canScroll&&scroller.scrollHeight>scroller.clientHeight+1)break;
        scroller=scroller.parentElement;
      }
      if(!scroller||scroller===document.body)scroller=document.scrollingElement||document.documentElement;
      if(scroller===document.scrollingElement||scroller===document.documentElement||scroller===document.body){
        top=calendar.getBoundingClientRect().top+window.pageYOffset-offset;
        window.scrollTo({top:Math.max(0,top),behavior:"smooth"});
        return;
      }
      top=scroller.scrollTop+calendar.getBoundingClientRect().top-scroller.getBoundingClientRect().top-offset;
      scroller.scrollTo({top:Math.max(0,top),behavior:"smooth"});
    }

    function adminSection(){
      if(!state.adminOpen||!hasPermission("equipment_rentals.manage_items"))return"";
      var site=activeSite();
      var items=allItemsForSite();
      return`
        <section class="card rounded-xl p-4">
          <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h2 class="heading-5 text-secondary-900">Administration location materiel</h2>
              <p class="text-body-sm text-secondary-500">${esc(site?.name||"Site actif")}</p>
            </div>
            <button type="button" class="btn btn-primary btn-sm" data-item-new>Ajouter un materiel</button>
          </div>
          <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
            ${items.map(function(item){
              var category=categoryById(item.categoryId);
              return`
                <article class="rounded-xl border border-surface-200 p-3">
                  <div class="mb-2 flex items-start gap-3">
                    ${imageHtml(item.photoUrl,item.name,"h-14 w-14")}
                    <div class="min-w-0 flex-1">
                      <div class="mb-2 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                          <div class="flex min-w-0 items-center gap-2">
                            <span class="h-3 w-3 shrink-0 rounded-full" style="background:${esc(item.color||"#95002e")}"></span>
                            <h3 class="truncate text-label text-secondary-900">${esc(item.name)}</h3>
                          </div>
                          <p class="mt-1 text-caption text-secondary-500">${esc(category?.name||"Sans categorie")}${item.inventoryCode?` - ${esc(item.inventoryCode)}`:""}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                          <button type="button" class="btn btn-secondary btn-xs" data-item-edit="${item.id}">Modifier</button>
                          <button type="button" class="btn btn-ghost btn-xs" data-item-delete="${item.id}">Masquer</button>
                        </div>
                      </div>
                      <p class="text-caption text-secondary-500">${esc(item.description||"")}</p>
                      <div class="mt-3 flex flex-wrap gap-2 text-ui-xs text-secondary-500">
                        <span class="badge badge-neutral">Demi-journee ${Number(item.halfDayPrice||0).toFixed(2)} €</span>
                        <span class="badge badge-neutral">Journee ${Number(item.dayPrice||0).toFixed(2)} €</span>
                        <span class="badge badge-neutral">Caution ${Number(item.depositAmount||0).toFixed(2)} €</span>
                      </div>
                    </div>
                  </div>
                </article>
              `;
            }).join("")||'<p class="rounded-xl border border-dashed border-surface-200 p-4 text-center text-body-sm text-secondary-400">Aucun materiel sur ce site.</p>'}
          </div>
        </section>
      `;
    }

    function defaultForm(slot){
      var selectedSlot=slot||"morning";
      var range=slotRange(state.focusDate,selectedSlot);
      var item=selectedItem()||firstAvailableItem(range[0],range[1])||allItemsForSite()[0];
      return{
        equipmentItemId:item?.id?String(item.id):"",
        periodType:selectedSlot==="full_day"?"day":"half_day",
        slot:selectedSlot,
        status:"reserved",
        title:"",
        contactPhone:"",
        date:dateKey(state.focusDate),
        startAt:range[0],
        endAt:range[1],
        notes:""
      };
    }

    function openCreate(slot){
      state.notice=null;
      state.modal={mode:"create",form:defaultForm(slot)};
      render();
    }

    function openEdit(id){
      var rental=rentalsForSite().find(function(item){return item.id===Number(id)});
      if(!rental||!canUpdate(rental))return;
      state.notice=null;
      state.focusDate=parseDate(rental.startAt);
      state.modal={mode:"edit",id:rental.id,form:{
        equipmentItemId:String(rental.equipmentItemId),
        periodType:rental.periodType,
        slot:rental.slot,
        status:rental.status,
        title:rental.title||"",
        contactPhone:rental.contactPhone||"",
        date:rental.startAt.slice(0,10),
        startAt:rental.startAt,
        endAt:rental.endAt,
        notes:rental.notes||""
      }};
      render();
    }

    function defaultItemForm(){
      var items=allItemsForSite();
      return{
        siteId:String(state.selectedSiteId||""),
        categoryId:String(state.data.equipmentCategories[0]?.id||""),
        categoryName:"",
        name:"",
        inventoryCode:"",
        description:"",
        color:"#95002e",
        halfDayPrice:"0",
        dayPrice:"0",
        showDayPrice:true,
        depositAmount:"0",
        photoUrl:"",
        sortOrder:String((items.length+1)*10)
      };
    }

    function openItemCreate(){
      state.notice=null;
      state.itemModal={mode:"create",form:defaultItemForm()};
      render();
    }

    function openItemEdit(id){
      var item=allItemsForSite().find(function(entry){return entry.id===Number(id)});
      if(!item||!hasPermission("equipment_rentals.manage_items"))return;
      state.notice=null;
      state.itemModal={mode:"edit",id:item.id,form:{
        siteId:String(item.siteId),
        categoryId:item.categoryId?String(item.categoryId):"",
        categoryName:"",
        name:item.name||"",
        inventoryCode:item.inventoryCode||"",
        description:item.description||"",
        color:item.color||"#95002e",
        halfDayPrice:String(item.halfDayPrice??0),
        dayPrice:String(item.dayPrice??0),
        showDayPrice:item.showDayPrice!==false,
        depositAmount:String(item.depositAmount??0),
        photoUrl:item.photoUrl||"",
        sortOrder:String(item.sortOrder??100)
      }};
      render();
    }

    function syncSlotFields(){
      if(!state.modal)return;
      var form=state.modal.form;
      var day=parseDate(`${form.date}T00:00`);
      var range=slotRange(day,form.slot);
      form.periodType=form.slot==="full_day"?"day":"half_day";
      form.startAt=range[0];
      form.endAt=range[1];
    }

    function modalHtml(){
      if(!state.modal)return"";
      var form=state.modal.form;
      var isEdit=state.modal.mode==="edit";
      var modalItem=itemById(form.equipmentItemId)||itemsForSite()[0]||null;
      var hours=siteHours(activeSite());
      return`<div class="equipment-modal-backdrop" data-close-modal>
        <div class="equipment-modal-panel" data-modal-panel>
          <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-surface-200 bg-white px-6 py-4">
            <div>
              <h2 class="heading-5 text-secondary-900">${isEdit?"Modifier location":"Location rapide"}</h2>
              <p class="mt-1 text-body-sm text-secondary-500">Materiel branche sur le planning CRM.</p>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" data-close>Fermer</button>
          </div>
          <form class="space-y-4 p-6" data-rental-form>
            ${state.notice?`<div class="rounded-xl border ${state.notice.type==="success"?"border-success-200 bg-success-50 text-success-800":"border-danger-200 bg-danger-50 text-danger-800"} p-3 text-body-sm">${esc(state.notice.message)}</div>`:""}
            <input type="hidden" name="status" value="${esc(isEdit?form.status||"reserved":"reserved")}">
            <input type="hidden" name="periodType" value="${esc(form.periodType)}">
            <input type="hidden" name="startAt" value="${esc(form.startAt)}">
            <input type="hidden" name="endAt" value="${esc(form.endAt)}">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-[1fr_88px]">
              <label class="form-field">
                <span class="label label-required">Materiel</span>
                <select class="select-native input" name="equipmentItemId" required>
                  ${itemsForSite().map(function(item){return`<option value="${item.id}" ${String(item.id)===form.equipmentItemId?"selected":""}>${esc(item.name)}${item.inventoryCode?` - ${esc(item.inventoryCode)}`:""}</option>`}).join("")}
                </select>
              </label>
              <div>
                <span class="label">Photo</span>
                <div class="mt-2">${imageHtml(modalItem?.photoUrl,modalItem?.name||"Materiel","h-16 w-16")}</div>
              </div>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <label class="form-field">
                <span class="label">Date</span>
                <input class="input" type="date" name="date" value="${esc(form.date)}">
              </label>
              <label class="form-field">
                <span class="label">Creneau</span>
                <select class="select-native input" name="slot">
                  <option value="morning" ${form.slot==="morning"?"selected":""}>Matin (${esc(hours.morningStart)}-${esc(hours.morningEnd)})</option>
                  <option value="afternoon" ${form.slot==="afternoon"?"selected":""}>Apres-midi (${esc(hours.afternoonStart)}-${esc(hours.afternoonEnd)})</option>
                  <option value="full_day" ${form.slot==="full_day"?"selected":""}>Journee (${esc(hours.morningStart)}-${esc(hours.afternoonEnd)})</option>
                </select>
              </label>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <label class="form-field">
                <span class="label">Client / chantier</span>
                <input class="input" name="title" value="${esc(form.title)}" placeholder="Ex : Client Dupont">
              </label>
              <label class="form-field">
                <span class="label">Telephone</span>
                <input class="input" name="contactPhone" value="${esc(form.contactPhone)}" placeholder="06 12 34 56 78">
              </label>
            </div>
            <label class="form-field">
              <span class="label">Notes</span>
              <textarea class="textarea" name="notes" rows="3">${esc(form.notes)}</textarea>
            </label>
            <button type="submit" class="btn btn-primary btn-md w-full" ${state.saving?"disabled":""}>${state.saving?"Enregistrement...":isEdit?"Modifier la location":"Enregistrer la location"}</button>
          </form>
        </div>
      </div>`;
    }

    function itemModalHtml(){
      if(!state.itemModal)return"";
      var form=state.itemModal.form;
      var isEdit=state.itemModal.mode==="edit";
      return`<div class="equipment-modal-backdrop" data-close-modal>
        <div class="equipment-modal-panel" data-modal-panel>
          <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-surface-200 bg-white px-6 py-4">
            <div>
              <h2 class="heading-5 text-secondary-900">${isEdit?"Modifier materiel":"Ajouter un materiel"}</h2>
              <p class="mt-1 text-body-sm text-secondary-500">${esc(activeSite()?.name||"Site actif")}</p>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" data-close>Fermer</button>
          </div>
          <form class="space-y-4 p-6" data-item-form>
            ${state.notice?`<div class="rounded-xl border ${state.notice.type==="success"?"border-success-200 bg-success-50 text-success-800":"border-danger-200 bg-danger-50 text-danger-800"} p-3 text-body-sm">${esc(state.notice.message)}</div>`:""}
            <input type="hidden" name="siteId" value="${esc(form.siteId)}">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <label class="form-field">
                <span class="label label-required">Nom</span>
                <input class="input" name="name" value="${esc(form.name)}" required maxlength="160" placeholder="Ex : Ponceuse parquet">
              </label>
              <label class="form-field">
                <span class="label">Code inventaire</span>
                <input class="input" name="inventoryCode" value="${esc(form.inventoryCode)}" maxlength="80" placeholder="Ex : PON-001">
              </label>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <label class="form-field">
                <span class="label">Categorie</span>
                <select class="select-native input" name="categoryId">
                  <option value="">Sans categorie</option>
                  ${state.data.equipmentCategories.map(function(category){return`<option value="${category.id}" ${String(category.id)===form.categoryId?"selected":""}>${esc(category.name)}</option>`}).join("")}
                </select>
              </label>
              <label class="form-field">
                <span class="label">Nouvelle categorie</span>
                <input class="input" name="categoryName" value="${esc(form.categoryName)}" maxlength="120" placeholder="Ex : Poncage">
              </label>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
              <label class="form-field">
                <span class="label">Couleur</span>
                <input class="input h-11" type="color" name="color" value="${esc(form.color)}">
              </label>
              <label class="form-field">
                <span class="label">Demi-journee</span>
                <input class="input" type="number" step="0.01" min="0" name="halfDayPrice" value="${esc(form.halfDayPrice)}">
              </label>
              <label class="form-field">
                <span class="label">Journee</span>
                <input class="input" type="number" step="0.01" min="0" name="dayPrice" value="${esc(form.dayPrice)}">
              </label>
              <div class="form-field">
                <span class="label">Prix carte</span>
                <label class="flex min-h-11 items-center gap-2 rounded-lg border border-surface-200 px-3 text-body-sm font-semibold text-secondary-700">
                  <input class="checkbox" type="checkbox" name="showDayPrice" value="1" ${form.showDayPrice!==false?"checked":""}>
                  <span>Afficher</span>
                </label>
              </div>
              <label class="form-field">
                <span class="label">Caution</span>
                <input class="input" type="number" step="0.01" min="0" name="depositAmount" value="${esc(form.depositAmount)}">
              </label>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-[96px_1fr]">
              <div>
                <span class="label">Photo actuelle</span>
                <div class="mt-2">${imageHtml(form.photoUrl,form.name||"Materiel","h-20 w-20")}</div>
              </div>
              <label class="form-field">
                <span class="label">Photo</span>
                <input class="input" type="file" name="photoFile" accept="image/png,image/jpeg,image/webp">
              </label>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-[1fr_120px]">
              <label class="form-field">
                <span class="label">Description</span>
                <textarea class="textarea" name="description" rows="3" maxlength="255">${esc(form.description)}</textarea>
              </label>
              <label class="form-field">
                <span class="label">Ordre</span>
                <input class="input" type="number" name="sortOrder" value="${esc(form.sortOrder)}">
              </label>
            </div>
            <button type="submit" class="btn btn-primary btn-md w-full" ${state.saving?"disabled":""}>${state.saving?"Enregistrement...":isEdit?"Modifier le materiel":"Ajouter le materiel"}</button>
          </form>
        </div>
      </div>`;
    }

    function viewAllHtml(){
      if(!state.viewAll)return"";
      var upcoming=upcomingRentals();
      var selected=selectedItem();
      return`<div class="equipment-modal-backdrop" data-close-view-all>
        <div class="equipment-modal-panel equipment-modal-panel-wide" data-modal-panel>
          <div class="flex items-start justify-between gap-4 border-b border-surface-200 bg-white px-6 py-4">
            <div>
              <h2 class="heading-5 text-secondary-900">Toutes les prochaines locations</h2>
              <p class="mt-1 text-body-sm text-secondary-500">${selected?`Filtrees sur ${esc(selected.name)}`:"Site actif"}</p>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" data-close-view>Fermer</button>
          </div>
          <div class="grid max-h-[70vh] grid-cols-1 gap-3 overflow-y-auto p-6 md:grid-cols-2">
            ${upcoming.map(function(rental){return rentalCard(rental,false)}).join("")||'<p class="rounded-xl border border-dashed border-surface-200 p-6 text-center text-body-sm text-secondary-400">Aucune location a venir.</p>'}
          </div>
        </div>
      </div>`;
    }

    function render(){
      if(disposed)return;
      if(state.loading){
        removeTopSiteSelector();
        root.innerHTML='<div class="equipment-module-shell"><div class="card rounded-xl p-6 text-body-sm text-secondary-500">Chargement location materiel...</div></div>';
        return;
      }
      if(state.error){
        removeTopSiteSelector();
        root.innerHTML=`<div class="equipment-module-shell"><div class="card rounded-xl border border-danger-200 bg-danger-50 p-6 text-danger-800">${esc(state.error)}</div></div>`;
        return;
      }

      var role=activeRole();
      var site=activeSite();
      var user=activeUser();
      root.innerHTML=`
        <div class="equipment-module-shell animate-fade-in space-y-6">
          <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h1 class="heading-2 text-secondary-900">Location materiel</h1>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              ${!role.blocked&&hasPermission("equipment_rentals.manage_items")?`<button type="button" class="btn btn-secondary btn-sm" data-admin-toggle>${state.adminOpen?"Fermer administration":"Administration"}</button>`:""}
              <button type="button" class="btn btn-primary btn-sm" data-new ${role.blocked||!hasPermission("equipment_rentals.create")?"disabled":""}>Nouvelle location</button>
            </div>
          </header>
          ${state.notice&&!state.modal&&!state.itemModal?`<div class="rounded-xl border ${state.notice.type==="success"?"border-success-200 bg-success-50 text-success-800":"border-danger-200 bg-danger-50 text-danger-800"} p-3 text-body-sm">${esc(state.notice.message)}</div>`:""}
          ${role.blocked?`<section class="card rounded-xl border border-dashed border-danger-200 bg-danger-50 p-6 text-center">
            <h2 class="heading-4 text-secondary-900">Module location materiel masque</h2>
            <p class="mx-auto mt-2 max-w-xl text-body-sm text-secondary-600">Ce profil ne possede pas le droit equipment_rentals.view ou le module locations-materiel.</p>
          </section>`:`${adminSection()}${resourcesSection()}${selectedItem()?calendarSection():""}`}
          ${modalHtml()}
          ${itemModalHtml()}
          ${viewAllHtml()}
        </div>
      `;
      removeTopSiteSelector();
      bindEvents();
    }

    function bindEvents(){
      root.querySelector("[data-new]")?.addEventListener("click",function(){openCreate()});
      root.querySelector("[data-admin-toggle]")?.addEventListener("click",function(){state.adminOpen=!state.adminOpen;render()});
      root.querySelector("[data-item-new]")?.addEventListener("click",function(){openItemCreate()});
      root.querySelectorAll("[data-item-edit]").forEach(function(button){
        button.addEventListener("click",function(){openItemEdit(button.dataset.itemEdit)});
      });
      root.querySelectorAll("[data-item-delete]").forEach(function(button){
        button.addEventListener("click",async function(){await deleteEquipmentItem(Number(button.dataset.itemDelete))});
      });
      root.querySelectorAll("[data-view-all]").forEach(function(button){
        button.addEventListener("click",function(){state.viewAll=true;render()});
      });
      root.querySelector("[data-item-category-filter]")?.addEventListener("change",function(event){
        state.itemCategoryId=event.currentTarget.value;
        clearSelectedItemIfHidden();
        render();
      });
      root.querySelector("[data-item-filter-clear]")?.addEventListener("click",function(){
        state.itemCategoryId="";
        render();
      });
      root.querySelectorAll("[data-item-filter]").forEach(function(button){
        button.addEventListener("click",function(){
          var nextItemId=button.dataset.itemFilter?Number(button.dataset.itemFilter):null;
          state.selectedItemId=nextItemId;
          render();
          if(nextItemId){
            window.requestAnimationFrame(function(){
              scrollCalendarIntoView();
            });
          }
        });
      });
      root.querySelector("[data-close-view]")?.addEventListener("click",function(){state.viewAll=false;render()});
      root.querySelectorAll("[data-close]").forEach(function(button){
        button.addEventListener("click",function(){state.modal=null;state.itemModal=null;state.notice=null;render()});
      });
      root.querySelector("[data-today]")?.addEventListener("click",function(){state.focusDate=new Date(now.getFullYear(),now.getMonth(),now.getDate());render()});
      root.querySelectorAll("[data-view]").forEach(function(button){
        button.addEventListener("click",function(){state.view=button.dataset.view;render()});
      });
      root.querySelectorAll("[data-move]").forEach(function(button){
        button.addEventListener("click",function(){state.focusDate=addDays(state.focusDate,Number(button.dataset.move));render()});
      });
      root.querySelectorAll("[data-day]").forEach(function(button){
        button.addEventListener("click",function(){state.focusDate=parseDate(`${button.dataset.day}T00:00`);state.view="day";render()});
      });
      root.querySelectorAll("[data-slot]").forEach(function(button){
        button.addEventListener("click",function(){openCreate(button.dataset.slot)});
      });
      root.querySelectorAll("[data-edit]").forEach(function(card){
        card.addEventListener("click",function(event){
          if(event.target.closest("[data-delete]"))return;
          event.stopPropagation();
          openEdit(card.dataset.edit);
        });
      });
      root.querySelectorAll("[data-delete]").forEach(function(button){
        button.addEventListener("click",async function(event){
          event.stopPropagation();
          await deleteRental(Number(button.dataset.delete));
        });
      });
      root.querySelector("[data-rental-form]")?.addEventListener("submit",saveRental);
      root.querySelector("[data-item-form]")?.addEventListener("submit",saveEquipmentItem);
      root.querySelector("[data-rental-form]")?.addEventListener("change",function(event){
        if(!state.modal)return;
        var field=event.target.name;
        state.modal.form[field]=event.target.value;
        if(field==="slot"||field==="date"){
          syncSlotFields();
          render();
        }
        if(field==="equipmentItemId"){
          render();
        }
      });
      root.querySelectorAll("[data-close-modal], [data-close-view-all]").forEach(function(backdrop){
        backdrop.addEventListener("click",function(event){
          if(event.target.dataset.closeModal!==void 0){
            state.modal=null;
            state.itemModal=null;
            state.notice=null;
            render();
          }
          if(event.target.dataset.closeViewAll!==void 0){
            state.viewAll=false;
            render();
          }
        });
      });
      root.querySelectorAll("[data-modal-panel]").forEach(function(panel){
        panel.addEventListener("click",function(event){event.stopPropagation()});
      });
    }

    async function saveRental(event){
      event.preventDefault();
      if(!state.modal)return;
      syncSlotFields();
      var formData=new FormData(event.target);
      var payload=Object.fromEntries(formData.entries());
      payload.status=state.modal.mode==="create"?"reserved":state.modal.form.status||"reserved";
      payload.periodType=state.modal.form.periodType;
      payload.slot=state.modal.form.slot;
      payload.startAt=state.modal.form.startAt;
      payload.endAt=state.modal.form.endAt;
      payload.actorUserId=state.selectedUserId;
      if(state.modal.id)payload.id=state.modal.id;
      state.saving=true;
      state.notice=null;
      render();
      try{
        var action=state.modal.mode==="edit"?"update_rental":"create_rental";
        var result=await api(action,payload);
        var saved=result.equipmentRental;
        var rentals=state.data.equipmentRentals;
        var index=rentals.findIndex(function(item){return item.id===saved.id});
        if(index>=0)rentals[index]=saved;
        else rentals.push(saved);
        state.notice={type:"success",message:state.modal.mode==="edit"?"Location modifiee.":"Location enregistree."};
        state.focusDate=parseDate(saved.startAt);
        state.modal=null;
      }catch(error){
        state.notice={type:"error",message:error instanceof Error?error.message:"Impossible d enregistrer la location."};
      }finally{
        state.saving=false;
        render();
      }
    }

    async function deleteRental(id){
      try{
        await api("delete_rental",{id,actorUserId:state.selectedUserId});
        state.data.equipmentRentals=state.data.equipmentRentals.filter(function(item){return item.id!==id});
        state.notice={type:"success",message:"Location supprimee."};
      }catch(error){
        state.notice={type:"error",message:error instanceof Error?error.message:"Impossible de supprimer la location."};
      }finally{
        render();
      }
    }

    function fileToDataUrl(file){
      return new Promise(function(resolve,reject){
        if(!file){
          resolve("");
          return;
        }
        var reader=new FileReader();
        reader.onload=function(){resolve(String(reader.result||""))};
        reader.onerror=function(){reject(new Error("Lecture de la photo impossible"))};
        reader.readAsDataURL(file);
      });
    }

    async function saveEquipmentItem(event){
      event.preventDefault();
      if(!state.itemModal)return;
      var form=event.target;
      var formData=new FormData(form);
      var payload=Object.fromEntries(formData.entries());
      var photoFile=form.querySelector('input[name="photoFile"]')?.files?.[0]||null;
      delete payload.photoFile;
      payload.showDayPrice=form.querySelector('input[name="showDayPrice"]')?.checked?"1":"0";
      if(photoFile)payload.photoDataUrl=await fileToDataUrl(photoFile);
      payload.actorUserId=state.selectedUserId;
      if(state.itemModal.id)payload.id=state.itemModal.id;
      state.saving=true;
      state.notice=null;
      render();
      try{
        var result=await api("save_equipment_item",payload);
        var saved=result.equipmentItem;
        var items=state.data.equipmentItems;
        var index=items.findIndex(function(item){return item.id===saved.id});
        if(index>=0)items[index]=saved;
        else items.push(saved);
        if(result.equipmentCategory){
          var categories=state.data.equipmentCategories;
          var categoryIndex=categories.findIndex(function(category){return category.id===result.equipmentCategory.id});
          if(categoryIndex>=0)categories[categoryIndex]=result.equipmentCategory;
          else categories.push(result.equipmentCategory);
        }
        state.notice={type:"success",message:state.itemModal.mode==="edit"?"Materiel modifie.":"Materiel ajoute."};
        state.itemModal=null;
        state.adminOpen=true;
      }catch(error){
        state.notice={type:"error",message:error instanceof Error?error.message:"Impossible d enregistrer le materiel."};
      }finally{
        state.saving=false;
        render();
      }
    }

    async function deleteEquipmentItem(id){
      try{
        await api("delete_equipment_item",{id,actorUserId:state.selectedUserId});
        state.data.equipmentItems=state.data.equipmentItems.filter(function(item){return item.id!==id});
        state.notice={type:"success",message:"Materiel masque."};
        state.adminOpen=true;
      }catch(error){
        state.notice={type:"error",message:error instanceof Error?error.message:"Impossible de masquer le materiel."};
      }finally{
        render();
      }
    }

    render();
    loadData(null,{keepCurrent:true});
    function onGlobalSiteChanged(event){
      applyGlobalSite(event.detail?.siteId);
    }
    window.addEventListener(globalSiteEventName,onGlobalSiteChanged);
    window.setTimeout(function(){applyGlobalSite()},0);

    return function(){
      disposed=true;
      window.removeEventListener(globalSiteEventName,onGlobalSiteChanged);
      removeTopSiteSelector();
      root.innerHTML="";
    };
  },[]);

  return jsx.jsxs("div",{children:[
    jsx.jsx("style",{children:styles}),
    jsx.jsx("div",{ref:mountRef})
  ]});
}

export{EquipmentRentalsPage};
