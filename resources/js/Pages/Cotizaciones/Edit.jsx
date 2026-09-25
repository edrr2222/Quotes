import { useMemo } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

const fmt = (n) => '$' + Math.round(n || 0).toLocaleString('es-CO');

const CATEGORIA_LABELS = { obra_civil: 'Obra civil', hidraulico: 'Sistema hidráulico', electrico: 'Sistema eléctrico' };

// Los 3 documentos que debe generar el sistema (ver CLAUDE.md): la especificación va sola,
// o embebida junto con cada una de las dos cotizaciones (mano de obra / materiales).
const DOCUMENTOS = [
    { tipo: 'especificacion', label: 'Especificación del trabajo' },
    { tipo: 'especificacion_mano_obra', label: 'Especificación + Mano de obra' },
    { tipo: 'especificacion_materiales', label: 'Especificación + Materiales' },
];

export default function Edit({ cotizacion, resumen }) {
    const { data, setData, put, processing } = useForm({
        numero: cotizacion.numero ?? '',
        jornales: cotizacion.jornales,
        factor_prestacional: cotizacion.factor_prestacional,
        imprevistos_mano_obra_pct: cotizacion.imprevistos_mano_obra_pct,
        transporte_pct: cotizacion.transporte_pct,
        herramienta_pct: cotizacion.herramienta_pct,
        administracion_pct: cotizacion.administracion_pct,
        imprevistos_pct: cotizacion.imprevistos_pct,
        utilidad_pct: cotizacion.utilidad_pct,
        iva_pct: cotizacion.iva_pct,
        notas: cotizacion.notas ?? '',
        especificacion_items: cotizacion.especificacion_items.length ? cotizacion.especificacion_items : [
            { categoria: 'Obra civil', descripcion: '' },
        ],
        mano_obra_items: cotizacion.mano_obra_items.length ? cotizacion.mano_obra_items : [
            { cargo: 'Maestro de obra', numero_personas: 1, jornal_basico: 120000, arl_dia: null },
        ],
        material_items: cotizacion.material_items,
    });

    // Total en vivo solo para feedback inmediato mientras se edita; el backend recalcula al guardar.
    const totalManoObraEnVivo = useMemo(() => {
        return data.mano_obra_items.reduce((sum, it) => {
            const arl = it.arl_dia ?? (it.jornal_basico * (data.factor_prestacional - 1));
            return sum + (Number(it.jornal_basico) + Number(arl)) * data.jornales * Number(it.numero_personas || 0);
        }, 0);
    }, [data.mano_obra_items, data.jornales, data.factor_prestacional]);

    function actualizarItem(lista, index, campo, valor) {
        const copia = [...data[lista]];
        copia[index] = { ...copia[index], [campo]: valor };
        setData(lista, copia);
    }

    function agregarEspecificacion() {
        setData('especificacion_items', [...data.especificacion_items, { categoria: '', descripcion: '' }]);
    }

    function agregarManoObra() {
        setData('mano_obra_items', [...data.mano_obra_items, { cargo: '', numero_personas: 1, jornal_basico: 0, arl_dia: null }]);
    }

    function agregarMaterial(categoria) {
        setData('material_items', [...data.material_items, { categoria, descripcion: '', unidad: 'Und', cantidad: 1, valor_unitario: 0 }]);
    }

    function eliminarItem(lista, index) {
        setData(lista, data[lista].filter((_, i) => i !== index));
    }

    function guardar(e) {
        e.preventDefault();
        put(route('cotizaciones.update', cotizacion.id));
    }

    function generarDocumento(tipo) {
        router.post(route('documentos.generar', [cotizacion.id, tipo]));
    }

    return (
        <AppLayout>
            <Link href={route('proyectos.show', cotizacion.proyecto_id)} className="text-sm text-[#1F3864] hover:underline">
                ← {cotizacion.proyecto?.nombre}
            </Link>
            <h1 className="text-2xl font-bold text-[#1F3864] mt-1 mb-6">Cotización {cotizacion.numero}</h1>

            <form onSubmit={guardar} className="space-y-8">
                {/* Parámetros generales */}
                <section className="bg-white border rounded p-4">
                    <h2 className="font-semibold text-[#1F3864] mb-3">Parámetros</h2>
                    <div className="grid grid-cols-4 gap-3 text-sm">
                        <Campo label="Jornales por persona">
                            <input type="number" className="border rounded px-2 py-1 w-full" value={data.jornales}
                                onChange={e => setData('jornales', Number(e.target.value))} />
                        </Campo>
                        <Campo label="Factor prestacional">
                            <input type="number" step="0.01" className="border rounded px-2 py-1 w-full" value={data.factor_prestacional}
                                onChange={e => setData('factor_prestacional', Number(e.target.value))} />
                        </Campo>
                        <Campo label="Imprevistos M.O. (%)">
                            <PctInput value={data.imprevistos_mano_obra_pct} onChange={v => setData('imprevistos_mano_obra_pct', v)} />
                        </Campo>
                        <Campo label="Transporte (%)">
                            <PctInput value={data.transporte_pct} onChange={v => setData('transporte_pct', v)} />
                        </Campo>
                        <Campo label="Herramienta (%)">
                            <PctInput value={data.herramienta_pct} onChange={v => setData('herramienta_pct', v)} />
                        </Campo>
                        <Campo label="Administración (%)">
                            <PctInput value={data.administracion_pct} onChange={v => setData('administracion_pct', v)} />
                        </Campo>
                        <Campo label="Imprevistos gral. (%)">
                            <PctInput value={data.imprevistos_pct} onChange={v => setData('imprevistos_pct', v)} />
                        </Campo>
                        <Campo label="Utilidad (%)">
                            <PctInput value={data.utilidad_pct} onChange={v => setData('utilidad_pct', v)} />
                        </Campo>
                        <Campo label="IVA (%)">
                            <PctInput value={data.iva_pct} onChange={v => setData('iva_pct', v)} />
                        </Campo>
                    </div>
                </section>

                {/* Especificación del trabajo — se incluye en los 3 documentos generados */}
                <section className="bg-white border rounded p-4">
                    <div className="flex items-center justify-between mb-3">
                        <h2 className="font-semibold text-[#1F3864]">Especificación del trabajo</h2>
                        <button type="button" onClick={agregarEspecificacion} className="text-sm text-[#1F3864] hover:underline">+ Agregar ítem</button>
                    </div>
                    <p className="text-xs text-gray-500 mb-2">
                        Esta lista se incluye tal cual en los 3 documentos que puedes generar más abajo (sola, o junto con cada cotización).
                    </p>
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-gray-500 border-b">
                                <th className="w-40 py-1">Categoría</th>
                                <th>Descripción</th>
                                <th className="w-8"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.especificacion_items.map((item, i) => (
                                <tr key={i} className="border-b align-top">
                                    <td>
                                        <input list="categorias-especificacion" className="border rounded px-2 py-1 w-full"
                                            value={item.categoria ?? ''}
                                            onChange={e => actualizarItem('especificacion_items', i, 'categoria', e.target.value)} />
                                    </td>
                                    <td>
                                        <textarea rows={2} className="border rounded px-2 py-1 w-full"
                                            value={item.descripcion}
                                            onChange={e => actualizarItem('especificacion_items', i, 'descripcion', e.target.value)} />
                                    </td>
                                    <td><button type="button" onClick={() => eliminarItem('especificacion_items', i)} className="text-red-600">×</button></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    <datalist id="categorias-especificacion">
                        <option value="Obra civil" />
                        <option value="Sistema hidráulico" />
                        <option value="Sistema eléctrico" />
                    </datalist>
                </section>

                {/* Mano de obra */}
                <section className="bg-white border rounded p-4">
                    <div className="flex items-center justify-between mb-3">
                        <h2 className="font-semibold text-[#1F3864]">Mano de obra</h2>
                        <button type="button" onClick={agregarManoObra} className="text-sm text-[#1F3864] hover:underline">+ Agregar cargo</button>
                    </div>
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-gray-500 border-b">
                                <th className="py-1">Cargo</th>
                                <th className="w-20">Personas</th>
                                <th className="w-28">Jornal básico</th>
                                <th className="w-28">ARL/día (opcional)</th>
                                <th className="w-8"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.mano_obra_items.map((item, i) => (
                                <tr key={i} className="border-b">
                                    <td><input className="border rounded px-2 py-1 w-full" value={item.cargo}
                                        onChange={e => actualizarItem('mano_obra_items', i, 'cargo', e.target.value)} /></td>
                                    <td><input type="number" className="border rounded px-2 py-1 w-full" value={item.numero_personas}
                                        onChange={e => actualizarItem('mano_obra_items', i, 'numero_personas', Number(e.target.value))} /></td>
                                    <td><input type="number" className="border rounded px-2 py-1 w-full" value={item.jornal_basico}
                                        onChange={e => actualizarItem('mano_obra_items', i, 'jornal_basico', Number(e.target.value))} /></td>
                                    <td><input type="number" placeholder="auto" className="border rounded px-2 py-1 w-full" value={item.arl_dia ?? ''}
                                        onChange={e => actualizarItem('mano_obra_items', i, 'arl_dia', e.target.value === '' ? null : Number(e.target.value))} /></td>
                                    <td><button type="button" onClick={() => eliminarItem('mano_obra_items', i)} className="text-red-600">×</button></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    <p className="text-right font-semibold text-[#1F3864] mt-3">
                        Total mano de obra (estimado): {fmt(totalManoObraEnVivo)}
                    </p>
                </section>

                {/* Materiales por categoría */}
                {Object.entries(CATEGORIA_LABELS).map(([cat, label]) => (
                    <section key={cat} className="bg-white border rounded p-4">
                        <div className="flex items-center justify-between mb-3">
                            <h2 className="font-semibold text-[#1F3864]">{label}</h2>
                            <button type="button" onClick={() => agregarMaterial(cat)} className="text-sm text-[#1F3864] hover:underline">+ Agregar ítem</button>
                        </div>
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-gray-500 border-b">
                                    <th className="py-1">Descripción</th>
                                    <th className="w-16">Und.</th>
                                    <th className="w-20">Cant.</th>
                                    <th className="w-28">Vr. unitario</th>
                                    <th className="w-8"></th>
                                </tr>
                            </thead>
                            <tbody>
                                {data.material_items.map((item, i) => item.categoria === cat && (
                                    <tr key={i} className="border-b">
                                        <td><input className="border rounded px-2 py-1 w-full" value={item.descripcion}
                                            onChange={e => actualizarItem('material_items', i, 'descripcion', e.target.value)} /></td>
                                        <td><input className="border rounded px-2 py-1 w-full" value={item.unidad}
                                            onChange={e => actualizarItem('material_items', i, 'unidad', e.target.value)} /></td>
                                        <td><input type="number" step="0.001" className="border rounded px-2 py-1 w-full" value={item.cantidad}
                                            onChange={e => actualizarItem('material_items', i, 'cantidad', Number(e.target.value))} /></td>
                                        <td><input type="number" className="border rounded px-2 py-1 w-full" value={item.valor_unitario}
                                            onChange={e => actualizarItem('material_items', i, 'valor_unitario', Number(e.target.value))} /></td>
                                        <td><button type="button" onClick={() => eliminarItem('material_items', data.material_items.indexOf(item))} className="text-red-600">×</button></td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </section>
                ))}

                <button disabled={processing} className="bg-[#1F3864] text-white px-6 py-2 rounded">
                    Guardar cotización
                </button>
            </form>

            {/* Resumen calculado por el backend (fuente de verdad) */}
            <section className="bg-white border rounded p-4 mt-8">
                <h2 className="font-semibold text-[#1F3864] mb-3">Resumen (última versión guardada)</h2>
                <table className="w-full text-sm mb-4">
                    <tbody>
                        <Fila label="Mano de obra (con imprevistos)" valor={resumen.mano_obra.total_con_imprevistos} />
                        <Fila label="Materiales" valor={resumen.materiales.total_materiales} />
                        <Fila label="Costo directo" valor={resumen.costo_directo} />
                        <Fila label="Transporte" valor={resumen.transporte} />
                        <Fila label="Herramienta" valor={resumen.herramienta} />
                        <Fila label="AIU" valor={resumen.aiu} />
                        <Fila label="IVA" valor={resumen.iva} />
                        <Fila label="TOTAL GENERAL" valor={resumen.total_general} destacado />
                    </tbody>
                </table>

                <div className="flex flex-wrap gap-2">
                    {DOCUMENTOS.map(doc => (
                        <button key={doc.tipo} onClick={() => generarDocumento(doc.tipo)}
                            className="border border-[#1F3864] text-[#1F3864] px-3 py-1.5 rounded text-sm hover:bg-[#DCE6F1]">
                            Generar PDF — {doc.label}
                        </button>
                    ))}
                </div>

                {cotizacion.documentos?.length > 0 && (
                    <ul className="mt-4 text-sm divide-y">
                        {cotizacion.documentos.map(d => (
                            <li key={d.id} className="py-2 flex justify-between">
                                <span>{d.tipo} — {new Date(d.created_at).toLocaleString('es-CO')}</span>
                                <a href={route('documentos.descargar', d.id)} className="text-[#1F3864] hover:underline">Descargar</a>
                            </li>
                        ))}
                    </ul>
                )}
            </section>
        </AppLayout>
    );
}

function Campo({ label, children }) {
    return (
        <label className="block">
            <span className="text-xs text-gray-500">{label}</span>
            {children}
        </label>
    );
}

function PctInput({ value, onChange }) {
    return (
        <input type="number" step="1" className="border rounded px-2 py-1 w-full"
            value={Math.round(value * 100)}
            onChange={e => onChange(Number(e.target.value) / 100)} />
    );
}

function Fila({ label, valor, destacado }) {
    return (
        <tr className={destacado ? 'bg-[#1F3864] text-white font-semibold' : 'border-b'}>
            <td className="py-1.5 px-2">{label}</td>
            <td className="py-1.5 px-2 text-right">{fmt(valor)}</td>
        </tr>
    );
}
