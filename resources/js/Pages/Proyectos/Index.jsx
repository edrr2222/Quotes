import { useState } from 'react';
import { Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

const ESTADO_LABELS = {
    borrador: 'Borrador',
    en_cotizacion: 'En cotización',
    cotizado: 'Cotizado',
    aprobado: 'Aprobado',
    archivado: 'Archivado',
};

export default function Index({ proyectos }) {
    const [mostrarForm, setMostrarForm] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        nombre: '', cliente: '', municipio: '', departamento: '', descripcion: '',
    });

    function crear(e) {
        e.preventDefault();
        post(route('proyectos.store'), { onSuccess: () => reset() });
    }

    return (
        <AppLayout>
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-bold text-[#1F3864]">Proyectos</h1>
                <button
                    onClick={() => setMostrarForm(!mostrarForm)}
                    className="bg-[#1F3864] text-white px-4 py-2 rounded text-sm"
                >
                    {mostrarForm ? 'Cancelar' : '+ Nuevo proyecto'}
                </button>
            </div>

            {mostrarForm && (
                <form onSubmit={crear} className="bg-white border rounded p-4 mb-6 grid grid-cols-2 gap-3">
                    <input placeholder="Nombre del proyecto" className="border rounded px-3 py-2 col-span-2"
                        value={data.nombre} onChange={e => setData('nombre', e.target.value)} />
                    {errors.nombre && <p className="text-red-600 text-xs col-span-2">{errors.nombre}</p>}
                    <input placeholder="Cliente" className="border rounded px-3 py-2"
                        value={data.cliente} onChange={e => setData('cliente', e.target.value)} />
                    <input placeholder="Municipio" className="border rounded px-3 py-2"
                        value={data.municipio} onChange={e => setData('municipio', e.target.value)} />
                    <input placeholder="Departamento" className="border rounded px-3 py-2"
                        value={data.departamento} onChange={e => setData('departamento', e.target.value)} />
                    <textarea placeholder="Descripción" className="border rounded px-3 py-2 col-span-2"
                        value={data.descripcion} onChange={e => setData('descripcion', e.target.value)} />
                    <button disabled={processing} className="bg-[#1F3864] text-white px-4 py-2 rounded col-span-2">
                        Crear proyecto
                    </button>
                </form>
            )}

            <div className="bg-white border rounded divide-y">
                {proyectos.length === 0 && (
                    <p className="p-6 text-gray-500 text-sm">Todavía no tienes proyectos. Crea el primero arriba.</p>
                )}
                {proyectos.map(p => (
                    <Link key={p.id} href={route('proyectos.show', p.id)} className="flex items-center justify-between p-4 hover:bg-gray-50">
                        <div>
                            <p className="font-medium">{p.nombre}</p>
                            <p className="text-sm text-gray-500">
                                {p.cliente ?? 'Sin cliente'} · {p.municipio ?? 'Sin ubicación'}
                                {p.departamento ? `, ${p.departamento}` : ''}
                            </p>
                        </div>
                        <div className="text-right text-sm text-gray-500">
                            <p>{p.planos_count} planos · {p.cotizaciones_count} cotizaciones</p>
                            <span className="inline-block mt-1 text-xs bg-[#DCE6F1] text-[#1F3864] px-2 py-0.5 rounded">
                                {ESTADO_LABELS[p.estado]}
                            </span>
                        </div>
                    </Link>
                ))}
            </div>
        </AppLayout>
    );
}
