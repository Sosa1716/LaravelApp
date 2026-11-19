import fs from 'node:fs/promises';
import path from 'node:path';
import { Presentation, PresentationFile } from 'file:///C:/Users/vegag/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/@oai/artifact-tool/dist/artifact_tool.mjs';

const W=1280,H=720;
const C={navy:'#142A36', ink:'#182C35', muted:'#52656B', cream:'#F5F1E8', white:'#FFFDF8', amber:'#D99436', green:'#4D755E', pale:'#E7E1D4', red:'#AE6755'};
const p=Presentation.create({slideSize:{width:W,height:H}});
const font='Aptos';
function box(slide,x,y,w,h,fill='none',geom='rect',lineFill='none'){
 return slide.shapes.add({geometry:geom,position:{left:x,top:y,width:w,height:h},fill,line:{style:'solid',fill:lineFill,width:lineFill==='none'?0:1}});
}
function txt(slide,text,x,y,w,h,size,color=C.ink,bold=false,opts={}){
 const s=box(slide,x,y,w,h,'none'); s.text=text; s.text.style={fontFamily:font,fontSize:size,color,bold,alignment:opts.align||'left',verticalAlignment:opts.valign||'middle',wrap:true}; return s;
}
function base(title,kicker='PROPUESTA DIGITAL'){
 const s=p.slides.add(); s.background.fill=C.cream;
 box(s,0,0,12,H,C.amber);
 txt(s,kicker,68,36,500,22,13,C.green,true);
 txt(s,title,68,75,1140,58,34,C.navy,true);
 box(s,68,143,1144,1,C.pale);
 txt(s,'GESTIÓN DE RESTAURANTE · APP + API',68,676,700,18,11,C.muted,true);
 txt(s,String(p.slides.items.length).padStart(2,'0'),1160,674,52,20,12,C.green,true,{align:'right'});
 return s;
}
function note(s,t){s.speakerNotes.text=t;}

// 1 Cover
{
 const s=p.slides.add(); s.background.fill=C.navy;
 box(s,0,0,18,H,C.amber);
 txt(s,'PROPUESTA DE SOLUCIÓN',80,88,620,25,15,'#E6B15E',true);
 txt(s,'Operación de\nrestaurante en\nun solo flujo',80,145,770,240,56,C.white,true);
 txt(s,'Aplicación móvil para comandas, cocina, mesas y cobro',84,420,715,70,24,'#DAE2DF',false);
 box(s,910,110,2,470,'#41606A');
 txt(s,'FRONTEND',952,160,235,24,13,'#E6B15E',true);
 txt(s,'Ionic + Angular',952,196,260,45,24,C.white,true);
 txt(s,'BACKEND',952,315,235,24,13,'#E6B15E',true);
 txt(s,'Laravel API',952,350,260,45,24,C.white,true);
 txt(s,'Una propuesta construida con los dos proyectos',84,612,700,25,17,'#DAE2DF',false);
 txt(s,'2026',1110,632,100,24,14,'#DAE2DF',true,{align:'right'});
 note(s,'Presentar la propuesta como una solución operativa compuesta por dos piezas que ya existen: una app Ionic/Angular y una API Laravel.');
}
// 2 problem
{
 const s=base('El reto operativo','CONTEXTO');
 txt(s,'Cuando la comanda depende de varios pasos manuales, se pierde continuidad entre el salón y la cocina.',68,178,1080,76,28,C.navy,true);
 const items=[['01','Mesa','¿Cuál está libre y quién la atiende?'],['02','Pedido','¿Qué pidió cada mesa y con qué indicaciones?'],['03','Cocina','¿Qué sigue pendiente, en preparación o listo?'],['04','Cobro','¿La cuenta quedó cerrada y la mesa disponible?']];
 items.forEach((a,i)=>{const y=300+i*78; txt(s,a[0],72,y,48,32,16,C.amber,true); txt(s,a[1],140,y,190,32,21,C.navy,true); txt(s,a[2],340,y,800,32,19,C.muted,false); if(i<3)box(s,140,y+49,1000,1,C.pale);});
 note(s,'Describir el problema como una necesidad de coordinación y seguimiento. No afirmar pérdidas o ahorros cuantificados: los proyectos no contienen métricas de operación.');
}
// 3 solution
{
 const s=base('La propuesta','SOLUCIÓN');
 txt(s,'Una app para el personal y una API central para mantener el mismo registro de mesas, órdenes y pagos.',68,174,1070,66,26,C.navy,true);
 const x=[70,460,850];
 const heads=['Salón','Cocina','Administración'];
 const bodies=['Consulta mesas y menú; crea comandas con cantidades y notas.','Ve órdenes y avanza su estado: pendiente, preparando, listo y entregado.','Administra platillos, categorías y mesas; consulta órdenes y pagos.'];
 x.forEach((xx,i)=>{txt(s,heads[i],xx,290,300,34,24,i===1?C.green:C.navy,true); box(s,xx,337,4,138,i===1?C.green:C.amber); txt(s,bodies[i],xx+20,339,300,132,20,C.muted,false,{valign:'top'});});
 txt(s,'Laravel concentra las reglas y los datos; Ionic ofrece la experiencia móvil.',70,542,1080,40,22,C.navy,true);
 note(s,'La app está orientada a empleados autenticados con roles. El catálogo, la toma de comanda y el monitor de cocina aparecen implementados en las pantallas del proyecto móvil. La administración de catálogo y mesas está expuesta en la API Laravel.');
}
// 4 flow
{
 const s=base('Flujo de una orden','OPERACIÓN');
 const steps=[['1','Elegir mesa'],['2','Armar comanda'],['3','Preparar'],['4','Entregar'],['5','Cobrar']];
 const xs=[75,305,535,765,995];
 steps.forEach((a,i)=>{
  box(s,xs[i],263,150,150,i===4?C.green:C.navy,'ellipse');
  txt(s,a[0],xs[i],281,150,36,18,'#E6B15E',true,{align:'center'});
  txt(s,a[1],xs[i]-35,333,220,38,18,C.white,true,{align:'center'});
  if(i<4){box(s,xs[i]+156,333,68,2,C.amber);}
 });
 txt(s,'El total se calcula en el servidor. El pago registra efectivo o tarjeta y libera la mesa.',90,488,1090,64,23,C.navy,true,{align:'center'});
 txt(s,'Reglas incluidas en la API: pago permitido al entregar; una orden pagada no se cobra dos veces.',90,574,1090,34,16,C.muted,false,{align:'center'});
 note(s,'Recorrer el flujo. Al crear la comanda, el servidor calcula precios y total, marca ocupada la mesa y registra al usuario autenticado. Para cobrar, exige que la orden esté entregada y evita duplicar pagos.');
}
// 5 app
{
 const s=base('La experiencia móvil','APLICACIÓN IONIC + ANGULAR');
 txt(s,'Diseñada alrededor del trabajo en el salón y la cocina.',68,174,1000,48,25,C.navy,true);
 const rows=[['SALÓN','Vista de mesas libres y ocupadas; filtro por estado.'],['COMANDA','Búsqueda y categorías; cantidades y notas para cocina.'],['COCINA','Monitor de órdenes y actualización de estados.'],['COBRO','Selección de método: efectivo o tarjeta.']];
 rows.forEach((a,i)=>{const y=260+i*84; txt(s,a[0],76,y,210,34,14,i===2?C.green:C.amber,true); txt(s,a[1],300,y-1,850,42,21,C.ink,false); if(i<3)box(s,76,y+54,1070,1,C.pale);});
 note(s,'Mencionar también el inicio de sesión, el token de acceso y la configuración de URL del servidor, visibles en el código del frontend.');
}
// 6 backend
{
 const s=base('Reglas y datos centralizados','API LARAVEL');
 txt(s,'La API aplica permisos por rol y mantiene consistencia en cada operación.',68,175,1100,52,25,C.navy,true);
 const left=[['Autenticación','Inicio y cierre de sesión con token'],['Roles','Administrador, Mesero y Cocina'],['Catálogo','Platillos, categorías y disponibilidad'],['Operación','Mesas, órdenes, detalles y pagos']];
 left.forEach((a,i)=>{const y=264+i*82; txt(s,a[0],76,y,260,32,20,C.green,true); txt(s,a[1],350,y,750,32,19,C.muted,false);});
 box(s,76,600,1090,2,C.pale);
 txt(s,'Laravel 12 · API REST · Sanctum',76,615,500,26,16,C.navy,true);
 note(s,'Explicar que el backend sirve de fuente de verdad. Los endpoints protegidos usan autenticación Sanctum y middleware de roles. Los datos contemplan usuarios, roles, categorías, platillos, mesas, órdenes, detalles y pagos.');
}
// 7 value
{
 const s=base('Qué aporta la propuesta','VALOR ESPERADO');
 const left=[['Visibilidad','El equipo consulta el estado de mesas y órdenes.'],['Trazabilidad','La orden conserva responsable, artículos, notas y estado.'],['Consistencia','El servidor calcula importes y aplica reglas de pago.'],['Coordinación','Salón, cocina y administración trabajan sobre el mismo flujo.']];
 left.forEach((a,i)=>{const y=197+i*105; txt(s,a[0],76,y,260,35,22,C.navy,true); txt(s,a[1],350,y,790,46,20,C.muted,false); if(i<3)box(s,76,y+64,1080,1,C.pale);});
 txt(s,'Beneficios esperados; medirlos durante un piloto antes de cuantificarlos.',76,625,1060,28,15,C.red,true);
 note(s,'Presentar estos puntos como resultados esperados del diseño, no como mejoras ya medidas. Proponer medir tiempos de captura, errores de comanda, tiempo de atención y conciliación de pagos en una prueba piloto.');
}
// 8 close
{
 const s=p.slides.add(); s.background.fill=C.navy; box(s,0,0,14,H,C.amber);
 txt(s,'PROPUESTA',78,84,360,24,14,'#E6B15E',true);
 txt(s,'Un flujo claro para\nservir mejor',78,145,900,130,48,C.white,true);
 txt(s,'Mesa → comanda → cocina → entrega → pago',82,312,1050,45,24,'#DCE4E1',false);
 box(s,82,405,1040,1,'#41606A');
 txt(s,'Siguiente paso',82,445,255,30,18,'#E6B15E',true);
 txt(s,'Probar el flujo completo con usuarios y datos de un restaurante piloto.',82,490,1050,60,23,C.white,false);
 txt(s,'¿Preguntas?',82,618,340,32,20,'#DCE4E1',true);
 note(s,'Cerrar con el siguiente paso: validar el flujo en un piloto, revisar permisos y conectividad en el dispositivo objetivo y acordar indicadores de evaluación.');
}

const build=path.resolve('.codex-build');
await fs.mkdir(build,{recursive:true});
for(let i=0;i<p.slides.items.length;i++){
 const blob=await p.export({slide:p.slides.items[i],format:'png',scale:1});
 await fs.writeFile(path.join(build,`slide-${String(i+1).padStart(2,'0')}.png`),new Uint8Array(await blob.arrayBuffer()));
}
const draft=path.join(build,'propuesta-restaurante-draft.pptx');
await (await PresentationFile.exportPptx(p)).save(draft);
console.log(`Draft created: ${draft}; slides=${p.slides.items.length}`);


