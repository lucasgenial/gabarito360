// Runtime único de gráficos da web (F3, docs/198): somente o que as telas usam.
// Exposto como window.echarts para graficos.js e turma-graficos.js.
import {use} from 'echarts/core';
import {BarChart,PieChart} from 'echarts/charts';
import {GridComponent,TooltipComponent,LegendComponent,AriaComponent,TitleComponent} from 'echarts/components';
import {SVGRenderer} from 'echarts/renderers';
import {LabelLayout} from 'echarts/features';
use([BarChart,PieChart,GridComponent,TooltipComponent,LegendComponent,AriaComponent,TitleComponent,SVGRenderer,LabelLayout]);
export * from 'echarts/core';
