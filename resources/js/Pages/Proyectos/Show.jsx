import { Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

const TIPO_LABELS = {
    arquitectonico: 'Arquitectónico',
    estructural: 'Estructural',
    hidraulico: 'Hidráulico',
    electrico: 'Eléctrico',
    otro: 'Otro',
};

const ESTADO_ANALISIS_LABELS = {
    pendiente: 'Sin analizar',
    procesando: 'Analizando…',
    listo: 'Analizado',
    error: 'Error al analizar',
};

export default function Show({ proyecto }) {
    const { data, setData, post, processing, reset } = useForm({ archivo: null, tipo: 'arquitectonico' });

    function subirPlano(e) {
        e.preventDefault();
        post(route('planos.store', proyecto.id), {
            forceFormData: true,
            onSuccess: () => reset(),
        });
    }

    function nuevaCotizacion() {
        router.post(route('cotizaciones.store', proyecto.id));
    }

    return (
        <AppLayout>
            <Link href={route('proyectos.index')} className="text-sm text-[#1F3864] hover:underline">← Proyectos</Link>
            <h1 className="text-2xl font-bold text-[#1F3864] mt-1 mb-1">{proyecto.nombre}</h1>
            <p className="text-gray-500 mb-6">
                {proyecto.cliente ?? 'Sin cliente'} · {proyecto.municipio ?? 'Sin ubicación'}
                {proyecto.departamento ? `, ${proyecto.departamento}` : ''}
            </p>

            <div className="grid grid-cols-2 gap-6">
                {/* Planos */}
                <section className="bg-white border rounded p-4">
                    <h2 className="font-semibold text-[#1F3864] mb-3">Planos</h2>

                    <form onSubmit={subirPlano} className="flex gap-2 mb-4">
                        <select className="border rounded px-2 py-1 text-sm" value={data.tipo}
                            onChange={e => setData('tipo', e.target.value)}>
                            {Object.entries(TIPO_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
                        </select>
                        <input type="file" accept="application/pdf" className="text-sm flex-1"
                            onChange={e => setData('archivo', e.target.files[0])} />
                        <button disabled={processing || !data.archivo} className="bg-[#1F3864] text-white px-3 py-1 rounded text-sm">
                            Subir
                        </button>
                    </form>

                    <ul className="divide-y text-sm">
                        {proyecto.planos.length === 0 && <li className="text-gray-500 py-2">Sin planos subidos.</li>}
                        {proyecto.planos.map(plano => (
                            <li key={plano.id} className="py-2">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="font-medium">{plano.nombre_original}</p>
                                        <p className="text-xs text-gray-500">
                                            {TIPO_LABELS[plano.tipo]} · {ESTADO_ANALISIS_LABELS[plano.estado_analisis]}
                                        </p>
                                    </div>
                                    <div className="flex gap-2 text-xs">
                                        <button
                                            onClick={() => router.post(route('planos.analizar', plano.id))}
                                            disabled={plano.estado_analisis === 'procesando'}
                                            className="text-[#1F3864] hover:underline"
                                        >
                                            Analizar
                                        </button>
                                        <button
                                            onClick={() => router.delete(route('planos.destroy', plano.id))}
                                            className="text-red-600 hover:underline"
                                        >
                                            Eliminar
                                        </button>
                                    </div>
                                </div>
                                {plano.analisis_json && (
                                    <pre className="mt-2 bg-gray-50 border rounded p-2 text-xs overflow-x-auto">
                                        {JSON.stringify(plano.analisis_json, null, 2)}
                                    </pre>
                                )}
                            </li>
                        ))}
                    </ul>
                </section>

                {/* Cotizaciones */}
                <section className="bg-white border rounded p-4">
                    <div className="flex items-center justify-between mb-3">
                        <h2 className="font-semibold text-[#1F3864]">Cotizaciones</h2>
                        <button onClick={nuevaCotizacion} className="bg-[#1F3864] text-white px-3 py-1 rounded text-sm">
                            + Nueva cotización
                        </button>
                    </div>
                    <ul className="divide-y text-sm">
                        {proyecto.cotizaciones.length === 0 && <li className="text-gray-500 py-2">Sin cotizaciones aún.</li>}
                        {proyecto.cotizaciones.map(c => (
                            <li key={c.id} className="py-2">
                                <Link href={route('cotizaciones.edit', c.id)} className="text-[#1F3864] hover:underline font-medium">
                                    {c.numero}
                                </Link>
                                <span className="text-xs text-gray-500 ml-2">{c.estado}</span>
                            </li>
                        ))}
                    </ul>
                </section>
            </div>
        </AppLayout>
    );
}
