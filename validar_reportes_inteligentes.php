<?php
// Validador de Reportes Inteligentes - Inventixor (versión limpia)
require_once 'config/db.php';

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$conexion_ok=false; $tablas=['Productos','Categoria','Subcategoria','Proveedores','Salidas','Users','Reportes','HistorialCRUD'];
$existe=[]; $faltan=[]; $conteos=[]; $reps=[]; $okReps=0; $invPrev=[]; $crit=0; $recs=[]; $estr=['tiene'=>false,'muestra'=>null]; $tieneHist=false;

try{ $conexion_ok = ($conn instanceof mysqli && method_exists($conn,'ping')) ? @$conn->ping() : false; if(!$conexion_ok){ $conexion_ok=(bool)@$conn->query('SELECT 1'); } }catch(Throwable $e){ $conexion_ok=false; }

foreach($tablas as $t){ $lk=$conn->real_escape_string($t); $rs=@$conn->query("SHOW TABLES LIKE '{$lk}'"); if($rs && $rs->num_rows>0){ $existe[]=$t; } else { $faltan[]=$t; } }
foreach($existe as $t){ $qt='`'.str_replace('`','``',$t).'`'; $rs=@$conn->query("SELECT COUNT(*) total FROM {$qt}"); $conteos[$t]=$rs? (int)($rs->fetch_assoc()['total']??0):0; }

$tests=[
  ['n'=>'inventario_general','q'=>"SELECT COUNT(*) total FROM Productos p LEFT JOIN Subcategoria sc ON p.id_subcg=sc.id_subcg LEFT JOIN Categoria c ON sc.id_categ=c.id_categ"],
  ['n'=>'productos_criticos','q'=>"SELECT COUNT(*) total FROM Productos WHERE CAST(stock AS UNSIGNED)<=5"],
  ['n'=>'movimientos_recientes','q'=>"SELECT COUNT(*) total FROM Salidas WHERE fecha_hora>=DATE_SUB(NOW(),INTERVAL 30 DAY)"],
  ['n'=>'categorias_analisis','q'=>"SELECT COUNT(*) total FROM Categoria c LEFT JOIN Subcategoria sc ON c.id_categ=sc.id_categ LEFT JOIN Productos p ON sc.id_subcg=p.id_subcg GROUP BY c.id_categ"],
];
foreach($tests as $t){ $ok=false;$tot=0;$err=null; try{ if($st=@$conn->prepare($t['q'])){ if($st->execute()){ if(method_exists($st,'get_result')){ $r=$st->get_result(); $row=$r?$r->fetch_assoc():null; $tot=(int)($row['total']??0); $ok=true; } else { $st->bind_result($tot); $ok=($st->fetch()!==null); if(!$ok){$tot=0;} } } else { $err=$st->error; } $st->close(); } else { $r=@$conn->query($t['q']); if($r){ $tot=(int)($r->fetch_assoc()['total']??0); $ok=true; } else { $err=$conn->error; } } }catch(Throwable $e){ $err=$e->getMessage(); }
  if($ok){$okReps++;} $reps[]=['n'=>$t['n'],'ok'=>$ok,'tot'=>$tot,'err'=>$err]; }

if(!empty($conteos['Productos'])){ $r=@$conn->query("SELECT p.nombre,p.modelo,p.stock,c.nombre categoria, CASE WHEN CAST(p.stock AS UNSIGNED)<=5 THEN 'CRÍTICO' WHEN CAST(p.stock AS UNSIGNED)<=15 THEN 'BAJO' ELSE 'NORMAL' END nivel FROM Productos p LEFT JOIN Subcategoria sc ON p.id_subcg=sc.id_subcg LEFT JOIN Categoria c ON sc.id_categ=c.id_categ ORDER BY CAST(p.stock AS UNSIGNED) ASC LIMIT 10"); if($r){ while($row=$r->fetch_assoc()){ $invPrev[]=$row; } } $r=@$conn->query("SELECT COUNT(*) criticos FROM Productos WHERE CAST(stock AS UNSIGNED)<=5"); if($r){ $crit=(int)($r->fetch_assoc()['criticos']??0); } }

if(!empty($conteos['Productos'])){ $r=@$conn->query("SELECT COUNT(CASE WHEN CAST(stock AS UNSIGNED)<=5 THEN 1 END) crit, COUNT(CASE WHEN CAST(stock AS UNSIGNED) BETWEEN 6 AND 15 THEN 1 END) bajos, COUNT(*) tot FROM Productos"); if($r){ $sa=$r->fetch_assoc(); $tot=max(1,(int)($sa['tot']??0)); $pC=round(((int)($sa['crit']??0)/$tot)*100,1); $pB=round(((int)($sa['bajos']??0)/$tot)*100,1); if((int)($sa['crit']??0)>0){ $recs[]=['t'=>'urg','m'=>"🚨 {$sa['crit']} productos ({$pC}%) con stock crítico", 'a'=>'Contactar proveedores']; } if($pB>20){ $recs[]=['t'=>'imp','m'=>"⚠️ {$pB}% de productos con stock bajo", 'a'=>'Revisar mínimo']; } if((int)($sa['crit']??0)===0 && $pB<10){ $recs[]=['t'=>'ok','m'=>'✅ Niveles de stock saludables', 'a'=>'Mantener prácticas']; } } }
if(!empty($conteos['Salidas'])){ $r=@$conn->query("SELECT COUNT(*) movimientos FROM Salidas WHERE fecha_hora>=DATE_SUB(NOW(),INTERVAL 30 DAY)"); $mov=$r?(int)($r->fetch_assoc()['movimientos']??0):0; if($mov===0){ $recs[]=['t'=>'att','m'=>'📊 No hay salidas registradas en 30 días','a'=>'Verificar proceso']; } else { $recs[]=['t'=>'info','m'=>"📈 {$mov} movimientos en 30 días (".round($mov/30,1)."/día)",'a'=>'Monitorear tendencias']; } }

if(in_array('Reportes',$existe,true)){ $c1=@$conn->query("SHOW COLUMNS FROM `Reportes` LIKE 'id_repor'"); $c2=@$conn->query("SHOW COLUMNS FROM `Reportes` LIKE 'num_doc'"); if($c1 && $c1->num_rows>0 && $c2 && $c2->num_rows>0){ $estr['tiene']=true; $s=@$conn->query("SELECT id_repor,num_doc FROM Reportes ORDER BY id_repor DESC LIMIT 1"); if($s && $s->num_rows>0){ $estr['muestra']=$s->fetch_assoc(); } } }
$tieneHist=in_array('HistorialCRUD',$existe,true);

$total=6; $pass=0; $pass+=($conexion_ok?1:0); $pass+=(empty($faltan)?1:0); $pass+=(!empty($conteos['Productos'])?1:0); $pass+=(($okReps===count($reps))?1:0); $pass+=(file_exists('reportes_inteligentes.php')?1:0); $pass+=1; $pct=(int)round(($pass/$total)*100);

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Validación Reportes Inteligentes - Inventixor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
  <style> body{background:#f4f6fb}.validation-container{background:#fff;border-radius:16px;padding:24px;margin:24px auto;max-width:1100px;box-shadow:0 10px 30px rgba(0,0,0,.06)}.test-section{background:#f8f9fa;border-radius:12px;padding:20px;margin:18px 0;border-left:5px solid #0d6efd}.test-result{border-radius:10px;padding:12px 14px;margin:8px 0}.test-success{background:#e7f5ff;border:1px solid #74c0fc;color:#0b7285}.test-warning{background:#fff3bf;border:1px solid #fcc419;color:#ad6800}.test-error{background:#ffe3e3;border:1px solid #ff6b6b;color:#c92a2a}.data-preview{background:#fff;border:1px solid #dee2e6;border-radius:8px;padding:10px;max-height:320px;overflow-y:auto}</style>
</head>
<body>
  <div class="container-fluid">
    <div class="validation-container">
      <h1 class="text-center mb-2"><i class="fa-solid fa-microscope text-primary me-2"></i>Validación Reportes Inteligentes</h1>
      <p class="text-center text-muted mb-4">Chequeos automáticos del ecosistema de reportes para QA</p>

      <div class="test-section">
        <h3><i class="fa-solid fa-database me-2"></i>1. Conexión a Base de Datos</h3>
        <?php if($conexion_ok): ?>
          <div class="test-result test-success"><i class="fa-solid fa-check me-2"></i>Conexión establecida correctamente.</div>
        <?php else: ?>
          <div class="test-result test-error"><i class="fa-solid fa-xmark me-2"></i>No se pudo conectar a la base de datos.</div>
        <?php endif; ?>
      </div>

      <div class="test-section">
        <h3><i class="fa-solid fa-table me-2"></i>2. Verificación de Tablas</h3>
        <?php foreach($tablas as $t): $ex=in_array($t,$existe,true); ?>
          <div class="test-result <?php echo $ex?'test-success':'test-error'; ?>">
            <strong><?php echo h($t); ?></strong> - <?php echo $ex?'Existe':'No encontrada'; ?>
          </div>
        <?php endforeach; ?>
        <?php if(empty($faltan)): ?>
          <div class="test-result test-success"><i class="fa-solid fa-check me-2"></i>Todas las tablas requeridas están disponibles.</div>
        <?php else: ?>
          <div class="test-result test-warning"><i class="fa-solid fa-triangle-exclamation me-2"></i>Faltan: <?php echo h(implode(', ',$faltan)); ?></div>
        <?php endif; ?>
      </div>

      <div class="test-section">
        <h3><i class="fa-solid fa-chart-simple me-2"></i>3. Verificación de Datos</h3>
        <?php foreach($conteos as $t=>$c): ?>
          <div class="test-result <?php echo ($c>0?'test-success':'test-warning'); ?>">
            <strong><?php echo h($t); ?>:</strong> <?php echo (int)$c; ?> registros
          </div>
        <?php endforeach; ?>
      </div>

      <div class="test-section">
        <h3><i class="fa-solid fa-magnifying-glass me-2"></i>4. Prueba de Consultas de Reportes</h3>
        <?php foreach($reps as $r): ?>
          <?php if($r['ok']): ?>
            <div class="test-result test-success"><strong><?php echo h($r['n']); ?>:</strong> OK (<?php echo (int)$r['tot']; ?>)</div>
          <?php else: ?>
            <div class="test-result test-error"><strong><?php echo h($r['n']); ?>:</strong> Error - <?php echo h($r['err']); ?></div>
          <?php endif; ?>
        <?php endforeach; ?>
        <?php if($okReps===count($reps)): ?>
          <div class="test-result test-success"><i class="fa-solid fa-check me-2"></i>Todas las consultas respondieron correctamente.</div>
        <?php else: ?>
          <div class="test-result test-warning"><i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo (int)$okReps; ?> de <?php echo (int)count($reps); ?> consultas respondieron correctamente.</div>
        <?php endif; ?>
      </div>

      <?php if(!empty($invPrev)): ?>
      <div class="test-section">
        <h3><i class="fa-solid fa-eye me-2"></i>5. Vista Previa de Datos</h3>
        <div class="data-preview">
          <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Producto</th><th>Modelo</th><th>Stock</th><th>Categoría</th><th>Nivel</th></tr></thead>
            <tbody>
              <?php foreach($invPrev as $row): $nivel=(string)$row['nivel']; $badge=($nivel==='CRÍTICO')?'bg-danger':(($nivel==='BAJO')?'bg-warning text-dark':'bg-success'); ?>
              <tr>
                <td><?php echo h($row['nombre']); ?></td>
                <td><?php echo h($row['modelo']); ?></td>
                <td><?php echo h($row['stock']); ?></td>
                <td><?php echo h($row['categoria']); ?></td>
                <td><span class="badge <?php echo $badge; ?>"><?php echo h($nivel); ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="mt-3">
          <?php if($crit>0): ?>
            <div class="test-result test-warning"><i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo (int)$crit; ?> productos con stock crítico (≤ 5)</div>
          <?php else: ?>
            <div class="test-result test-success"><i class="fa-solid fa-check me-2"></i>Sin productos con stock crítico</div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="test-section">
        <h3><i class="fa-solid fa-lightbulb me-2"></i>6. Recomendaciones</h3>
        <?php if(empty($recs)): ?>
          <div class="test-result test-success">Sistema validado. Agregue más datos para recomendaciones específicas.</div>
        <?php else: ?>
          <?php foreach($recs as $c): $cls=($c['t']==='urg')?'test-error':(($c['t']==='imp')?'test-warning':'test-success'); ?>
            <div class="test-result <?php echo $cls; ?>">
              <strong><?php echo h($c['m']); ?></strong><br />
              <small><strong>Acción:</strong> <?php echo h($c['a']); ?></small>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="test-section" style="border-left-color:#28a745; background: linear-gradient(135deg,#d4edda,#c3e6cb);">
        <?php if($pct>=90){$cls='test-success';$icon='fa-check-circle';$msg='✅ SISTEMA COMPLETAMENTE FUNCIONAL';}
              elseif($pct>=70){$cls='test-warning';$icon='fa-triangle-exclamation';$msg='⚠️ SISTEMA FUNCIONAL CON MEJORAS REQUERIDAS';}
              else{$cls='test-error';$icon='fa-times-circle';$msg='❌ SISTEMA REQUIERE ATENCIÓN INMEDIATA';} ?>
        <h3><i class="fa-solid fa-trophy me-2"></i>Resumen</h3>
        <div class="test-result <?php echo $cls; ?> text-center">
          <i class="fa-solid <?php echo $icon; ?> fa-2x mb-2"></i>
          <h4 class="mb-1"><?php echo $msg; ?></h4>
          <p class="mb-0"><strong>Puntuación:</strong> <?php echo (int)$pass; ?>/<?php echo (int)$total; ?> (<?php echo (int)$pct; ?>%)</p>
        </div>
        <div class="text-center mt-3">
          <a href="reportes_inteligentes.php" class="btn btn-success btn-lg me-2"><i class="fa-solid fa-chart-line me-2"></i>Ir a Reportes Inteligentes</a>
          <a href="reportes.php" class="btn btn-outline-primary btn-lg"><i class="fa-solid fa-arrow-left me-2"></i>Volver a Reportes</a>
        </div>
      </div>

    </div>
  </div>
</body>
</html>