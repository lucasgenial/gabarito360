/** GridStack encaminha o ciclo de gestos das subgrades à grade raiz. */
export function conectarGestosGrade({root,grids,wrappers,validNode,begin,finish}){
 let active=null,disposed=false,epoch=0;
 function owner(el){
  const node=el?.gridstackNode;
  if(disposed||!el?.isConnected||!wrappers.has(el)||!node||!grids.includes(node.grid))return null;
  if(el.parentElement!==node.grid.el||el.getAttribute('gs-id')!==node.id||!validNode(node.id))return null;
  return node.grid;
 }
 root.on('dragstart resizestart',(event,el)=>{
  const grid=owner(el);if(active||!grid)return;
  const gesture={id:el.getAttribute('gs-id'),grid,el,epoch:++epoch,phase:'active',stopType:event.type==='dragstart'?'dragstop':'resizestop'};
  active=gesture;begin(gesture);
 });
 root.on('dragstop resizestop',(event,el)=>{
  if(!active||active.phase!=='active'||active.stopType!==event.type||active.el!==el||active.grid!==owner(el)||active.id!==el.getAttribute('gs-id'))return;
  active.phase='ended';finish(active);
 });
 return {
  current:gesture=>!disposed&&active===gesture,
  complete(gesture){if(active===gesture)active=null;},
  cancel(){active=null;},
  destroy(){disposed=true;active=null;root.off('dragstart resizestart dragstop resizestop');}
 };
}
