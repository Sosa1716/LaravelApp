import fs from 'node:fs/promises';
import path from 'node:path';
import { Presentation, PresentationFile } from 'file:///C:/Users/vegag/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/@oai/artifact-tool/dist/artifact_tool.mjs';
const W=1280,H=720;
const C={yellow:'#F6D43A',sun:'#FFE477',red:'#D6424A',blue:'#168CC7',deep:'#152B45',cream:'#FFF8E2',white:'#FFFFFF',ink:'#282E3A',muted:'#5C606A',line:'#E7D98E',green:'#3C795C'};
const p=Presentation.create({slideSize:{width:W,height:H}});
const root=path.resolve('.'), build=path.join(root,'.codex-build'), original='C:/Users/vegag/AppData/Local/Temp/codex-clipboard-874ec86f-3f3c-4a19-bafb-84d7ec6dd8e9.png';
const assetPaths=[original,path.join(build,'logo-crop.png'),path.join(build,'mascot-crop.png'),path.join(build,'waiter-left.png'),path.join(build,'waiter-right.png')];
const assetBytes=new Map(await Promise.all(assetPaths.map(async q=>[q,new Uint8Array(await fs.readFile(q))])));
function shape(s,x,y,w,h,fill,geom='rect',line='none') { return s.shapes.add({geometry:geom,position:{left:x,top:y,width:w,height:h},fill,line:{style:'solid',fill:line,width:line==='none'?0:1}}); }
function text(s,t,x,y,w,h,size,color=C.ink,bold=false,align='left') { const z=shape(s,x,y,w,h,'none','textbox'); z.text=t; z.text.style={fontSize:size,color,bold,alignment:align,verticalAlignment:'middle',wrap:true}; return z; }
function img(s,path,x,y,w,h,alt,fit='contain') { return s.images.add({blob:assetBytes.get(path),contentType:'image/png',alt,fit,position:{left:x,top:y,width:w,height:h}}); }
function frame(num,title,kicker='PROPUESTA') { const s=p.slides.add(); s.background.fill=C.cream; shape(s,0,0,W,18,C.yellow); text(s,kicker.toUpperCase(),64,35,450,23,14,C.red,true); text(s,title,64,68,990,60,34,C.deep,true); img(s,path.join(build,'logo-crop.png'),1030,28,190,105,'Logotipo de Antojería Pingüín'); shape(s,64,144,1152,2,C.deep); text(s,'ANTOJERÍA PINGÜÍN  ·  GESTIÓN DE RESTAURANTE',64,680,780,20,12,C.deep,true); text(s,String(num).padStart(2,'0'),1164,679,52,20,13,C.red,true,'right'); return s; }
function notes(s,t){s.speakerNotes.text=t;}
// 1 Cover
{
 const s=p.slides.add(); s.background.fill=C.yellow;
 shape(s,0,0,18,H,C.red); shape(s,54,55,644,604,C.cream,'roundRect');
 img(s,original,760,0,520,720,'Cartel original de Antojería Pingüín','contain');
 text(s,'ANTOJERÍA',92,83,570,36,23,C.blue,true);
 text(s,'PINGÜÍN',84,125,630,116,76,C.red,true);
 text(s,'Comandas claras.' + String.fromCharCode(10) + 'Servicio coordinado.',92,290,620,135,38,C.deep,true);
 shape(s,94,457,500,5,C.blue);
 text(s,'Una propuesta digital para conectar salón, cocina y cobro',94,488,550,76,22,C.ink,false);
 text(s,'APP MÓVIL  +  API LARAVEL',94,604,550,26,15,C.green,true);
 notes(s,'Abrir presentando la propuesta y señalar que está compuesta por dos proyectos: la aplicación móvil Ionic/Angular y el backend Laravel. El cartel original aporta la identidad visual del restaurante.');
}
// 2 problem
{
 const s=frame(p.slides.items.length+1,'Del salón a cocina, sin perder el hilo','EL RETO');
 text(s,'Cuatro momentos que necesitan hablar entre sí',70,172,890,42,24,C.deep,true);
 const data=[['01','MESA','¿Está libre o ya está ocupada?'],['02','COMANDA','¿Qué pidió cada mesa?'],['03','COCINA','¿Qué está pendiente o listo?'],['04','COBRO','¿Ya se pagó y se liberó la mesa?']];
 data.forEach((d,i)=>{const y=242+i*92; shape(s,70,y,70,58,i%2?C.blue:C.red,'roundRect'); text(s,d[0],70,y+5,70,43,19,C.white,true,'center'); text(s,d[1],166,y+2,205,50,19,C.deep,true); text(s,d[2],355,y+2,630,50,19,C.muted,false); shape(s,166,y+68,730,1,C.line);});
 img(s,path.join(build,'mascot-crop.png'),915,215,282,360,'Personaje ilustrado del cartel');
 notes(s,'Enmarcarlo como un reto de continuidad operativa. No se están afirmando tiempos de espera ni pérdidas medidos; se plantea ordenar el recorrido de la orden.');
}
// 3 solution / roles
{
 const s=frame(p.slides.items.length+1,'Una solución para cada rol','LA PROPUESTA');
 text(s,'Una sola operación, con tareas claras para cada equipo',70,171,1040,42,24,C.deep,true);
 const cards=[['SALÓN',C.red,'Mesas disponibles y ocupadas.','Comandas con cantidades y notas.'],['COCINA',C.blue,'Órdenes por estado.','Avance hasta marcar “listo”.'],['ADMINISTRACIÓN',C.green,'Catálogo y mesas.','Consulta de órdenes y pagos.']];
 cards.forEach((d,i)=>{const x=72+i*378; shape(s,x,246,340,270,C.white,'roundRect'); shape(s,x,246,340,15,d[1]); text(s,d[0],x+24,278,292,33,18,d[1],true); text(s,d[2],x+24,337,292,62,21,C.deep,true); text(s,d[3],x+24,421,292,66,18,C.muted,false);});
 img(s,path.join(build,'waiter-left.png'),52,518,120,144,'Personaje mesero del cartel');
 img(s,path.join(build,'waiter-right.png'),1095,518,120,144,'Personaje mesera del cartel');
 notes(s,'Describir las capacidades existentes en los proyectos: el frontend tiene vista de mesas, comanda y monitor de cocina; la API permite administrar catálogo y mesas con permisos de administrador.');
}
// 4 process diagram
{
 const s=frame(p.slides.items.length+1,'El recorrido de una orden','FLUJO OPERATIVO');
 text(s,'Desde la selección de mesa hasta volver a dejarla disponible',70,169,1080,42,23,C.deep,true);
 const steps=[['1','ELEGIR','mesa'],['2','CAPTURAR','comanda'],['3','PREPARAR','en cocina'],['4','ENTREGAR','al cliente'],['5','COBRAR','y liberar']];
 steps.forEach((d,i)=>{const x=71+i*235; shape(s,x,265,178,180,i===4?C.blue:C.deep,'roundRect'); shape(s,x+18,282,42,42,C.yellow,'ellipse'); text(s,d[0],x+18,284,42,38,18,C.deep,true,'center'); text(s,d[1],x+20,345,142,31,18,C.white,true,'center'); text(s,d[2],x+20,382,142,28,16,'#DCE8F1',false,'center'); if(i<4){shape(s,x+181,351,43,4,C.red);}});
 text(s,'El backend calcula el total y valida que el pago ocurra después de entregar.',92,507,1088,50,22,C.deep,true,'center');
 img(s,path.join(build,'mascot-crop.png'),536,565,180,92,'Personaje del cartel como remate visual');
 notes(s,'Explicar que Laravel obtiene los precios desde el menú y calcula el total. El pago se permite solo con la orden entregada; al registrar el pago, la orden se cierra y la mesa vuelve a estar disponible.');
}
// 5 mobile app
{
 const s=frame(p.slides.items.length+1,'La comanda se arma desde el móvil','APP IONIC + ANGULAR');
 text(s,'Búsqueda, cantidades y notas en el mismo recorrido',70,171,900,42,24,C.deep,true);
 const cols=[['01','Elige mesa','Consulta disponibilidad y selecciona el destino.'],['02','Agrega platillos','Busca por nombre o categoría y ajusta cantidades.'],['03','Añade indicaciones','Incluye notas para cocina antes de enviar.']];
 cols.forEach((d,i)=>{const x=72+i*365; shape(s,x,263,322,235,C.white,'roundRect'); text(s,d[0],x+22,284,76,45,24,C.red,true); text(s,d[1],x+22,344,270,38,21,C.deep,true); text(s,d[2],x+22,399,270,70,18,C.muted,false);});
 shape(s,72,548,1050,74,C.blue,'roundRect'); text(s,'Revisa la comanda y envíala a cocina con el total calculado por el servidor.',98,560,1000,45,20,C.white,true,'center');
 notes(s,'Mencionar que la app mantiene inicio de sesión con token, permite configurar la URL del servidor y ofrece búsqueda, categorías, cantidad y notas por artículo antes de enviar la orden.');
}
// 6 backend
{
 const s=frame(p.slides.items.length+1,'Reglas claras detrás de cada acción','API LARAVEL');
 text(s,'Autenticación y permisos protegen las operaciones del restaurante',70,171,1080,44,24,C.deep,true);
 const rows=[['ACCESO','Inicio de sesión y token de autenticación',C.red],['ROLES','Administrador · Mesero · Cocina',C.blue],['CATÁLOGO','Platillos, categorías y disponibilidad',C.green],['ÓRDENES','Mesa, responsable, artículos, cantidades y notas',C.red],['PAGOS','Efectivo o tarjeta; importe desde la orden',C.blue]];
 rows.forEach((d,i)=>{const y=242+i*73; shape(s,74,y,13,48,d[2]); text(s,d[0],108,y,250,43,17,d[2],true); text(s,d[1],360,y,780,43,19,C.ink,false); shape(s,108,y+55,1010,1,C.line);});
 img(s,path.join(build,'waiter-right.png'),1055,579,115,95,'Personaje del cartel');
 notes(s,'La API usa Sanctum y permisos por rol. El backend es fuente de verdad para los precios y el monto cobrado; evita pagos duplicados y devuelve la mesa a estado disponible después de pagar.');
}
// 7 value
{
 const s=frame(p.slides.items.length+1,'Lo que la propuesta busca mejorar','VALOR ESPERADO');
 img(s,path.join(build,'waiter-left.png'),73,217,170,342,'Personaje mesero del cartel');
 const v=[['VISIBILIDAD','El equipo consulta mesas y órdenes por estado.'],['TRAZABILIDAD','Cada orden conserva responsable y detalle.'],['CONSISTENCIA','Los importes y reglas de pago se validan en servidor.'],['COORDINACIÓN','Salón y cocina comparten el avance de la comanda.']];
 v.forEach((d,i)=>{const y=202+i*94; text(s,d[0],280,y,270,33,16,i%2?C.blue:C.red,true); text(s,d[1],532,y-2,605,45,19,C.ink,false); shape(s,280,y+54,840,1,C.line);});
 shape(s,280,600,840,42,C.yellow,'roundRect'); text(s,'Son beneficios esperados; el piloto permitirá medirlos.',296,604,808,32,16,C.deep,true,'center');
 notes(s,'Presentar los beneficios como expectativas del diseño, no como resultados comprobados. Recomendar un piloto y medir tiempo de captura, errores de pedido, tiempo entre estados y cierres de cuenta.');
}
// 8 close
{
 const s=p.slides.add(); s.background.fill=C.red; shape(s,0,0,18,H,C.yellow);
 img(s,path.join(build,'logo-crop.png'),80,62,390,210,'Logotipo de Antojería Pingüín tomado del cartel');
 text(s,'Una experiencia conectada' + String.fromCharCode(10) + 'de la mesa al pago',82,303,720,120,43,C.white,true);
 shape(s,82,464,622,5,C.yellow);
 text(s,'Siguiente paso',82,505,260,28,17,C.yellow,true);
 text(s,'Probar el flujo completo con el equipo del restaurante.',82,541,730,64,22,C.white,false);
 img(s,original,887,25,355,670,'Cartel original de Antojería Pingüín','contain');
 text(s,'¿Preguntas?',82,642,330,30,20,C.white,true);
 notes(s,'Cerrar proponiendo una validación piloto con el personal del restaurante y datos reales de menú y mesas. Preguntar qué flujo conviene probar primero.');
}
await fs.mkdir(build,{recursive:true});
for(let i=0;i<p.slides.items.length;i++){const blob=await p.export({slide:p.slides.items[i],format:'png',scale:1});await fs.writeFile(path.join(build,`redesign-${String(i+1).padStart(2,'0')}.png`),new Uint8Array(await blob.arrayBuffer()));}
await (await PresentationFile.exportPptx(p)).save(path.join(build,'restaurante-redesign-draft.pptx'));
console.log('Rendered and exported',p.slides.items.length,'slides');






