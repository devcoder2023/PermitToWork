import { router, useForm, usePage } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import { useState } from 'react';
import AppLayout from '@/components/layouts/app-layout';

type PermitType = {
    id: number;
    name_en: string;
    name_ar: string;
    duration_type: string;
    is_active: boolean;
};

type Props = {
    permitTypes: PermitType[];
};

export default function PermitTypeIndex({ permitTypes }: Props) {
    const { props } = usePage();
    const [editingId, setEditingId] = useState<number | null>(null);

    const { data, setData, patch, processing, errors, reset } = useForm({
        name_en: '',
        name_ar: '',
        is_active: true,
    });

    const startEdit = (permitType: PermitType) => {
        setEditingId(permitType.id);
        setData({
            name_en: permitType.name_en,
            name_ar: permitType.name_ar,
            is_active: permitType.is_active,
        });
    };

    const cancelEdit = () => {
        setEditingId(null);
        reset();
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        if (editingId) {
            patch(`/admin/permit-types/${editingId}`, {
                onSuccess: () => {
                    setEditingId(null);
                    reset();
                },
            });
        }
    };

    const toggleActive = (permitType: PermitType) => {
        router.patch(`/admin/permit-types/${permitType.id}`, {
            is_active: !permitType.is_active,
        });
    };

    return (
        <AppLayout title="Permit Types">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                        Permit Types
                    </h2>
                </div>

                {props.flash?.success && (
                    <div className="rounded-md bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                        {props.flash.success}
                    </div>
                )}

                <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    Permit types are pre-defined. You can edit their names and
                    toggle their active status.
                </p>

                <div className="overflow-hidden rounded-lg border border-[#e3e3e0] bg-white dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <table className="min-w-full divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A]">
                        <thead className="bg-[#f5f5f4] dark:bg-[#1a1a19]">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    Name (English)
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    Name (Arabic)
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    Duration
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    Status
                                </th>
                                <th className="px-6 py-3 text-right text-xs font-medium tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A]">
                            {permitTypes.map((permitType) => (
                                <tr key={permitType.id}>
                                    {editingId === permitType.id ? (
                                        <>
                                            <td className="px-6 py-4">
                                                <input
                                                    type="text"
                                                    value={data.name_en}
                                                    onChange={(e) =>
                                                        setData(
                                                            'name_en',
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-1.5 text-sm text-[#1b1b18] focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                                                />
                                                {errors.name_en && (
                                                    <p className="mt-1 text-xs text-red-500">
                                                        {errors.name_en}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-6 py-4">
                                                <input
                                                    type="text"
                                                    value={data.name_ar}
                                                    onChange={(e) =>
                                                        setData(
                                                            'name_ar',
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-1.5 text-sm text-[#1b1b18] focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                                                    dir="rtl"
                                                />
                                                {errors.name_ar && (
                                                    <p className="mt-1 text-xs text-red-500">
                                                        {errors.name_ar}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-sm whitespace-nowrap text-[#706f6c] dark:text-[#A1A09A]">
                                                {permitType.duration_type ===
                                                'daily'
                                                    ? 'Daily'
                                                    : 'Weekly'}
                                            </td>
                                            <td className="px-6 py-4 text-sm whitespace-nowrap">
                                                <label className="flex items-center">
                                                    <input
                                                        type="checkbox"
                                                        checked={data.is_active}
                                                        onChange={(e) =>
                                                            setData(
                                                                'is_active',
                                                                e.target
                                                                    .checked,
                                                            )
                                                        }
                                                        className="h-4 w-4 rounded border-[#e3e3e0] text-[#f53003] focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#FF4433] dark:focus:ring-[#FF4433]"
                                                    />
                                                    <span className="ml-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                        Active
                                                    </span>
                                                </label>
                                            </td>
                                            <td className="px-6 py-4 text-right text-sm whitespace-nowrap">
                                                <form
                                                    onSubmit={submit}
                                                    className="inline"
                                                >
                                                    <button
                                                        type="submit"
                                                        disabled={processing}
                                                        className="mr-2 text-[#f53003] hover:underline disabled:opacity-50 dark:text-[#FF4433]"
                                                    >
                                                        Save
                                                    </button>
                                                </form>
                                                <button
                                                    type="button"
                                                    onClick={cancelEdit}
                                                    className="text-[#706f6c] hover:underline dark:text-[#A1A09A]"
                                                >
                                                    Cancel
                                                </button>
                                            </td>
                                        </>
                                    ) : (
                                        <>
                                            <td className="px-6 py-4 text-sm font-medium whitespace-nowrap text-[#1b1b18] dark:text-[#EDEDEC]">
                                                {permitType.name_en}
                                            </td>
                                            <td
                                                className="px-6 py-4 text-sm whitespace-nowrap text-[#706f6c] dark:text-[#A1A09A]"
                                                dir="rtl"
                                            >
                                                {permitType.name_ar}
                                            </td>
                                            <td className="px-6 py-4 text-sm whitespace-nowrap text-[#706f6c] dark:text-[#A1A09A]">
                                                {permitType.duration_type ===
                                                'daily'
                                                    ? 'Daily'
                                                    : 'Weekly'}
                                            </td>
                                            <td className="px-6 py-4 text-sm whitespace-nowrap">
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        toggleActive(permitType)
                                                    }
                                                    className={`inline-flex rounded-full px-2 text-xs leading-5 font-semibold ${
                                                        permitType.is_active
                                                            ? 'bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-900/20 dark:text-green-400'
                                                            : 'bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-900/20 dark:text-red-400'
                                                    }`}
                                                >
                                                    {permitType.is_active
                                                        ? 'Active'
                                                        : 'Inactive'}
                                                </button>
                                            </td>
                                            <td className="px-6 py-4 text-right text-sm whitespace-nowrap">
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        startEdit(permitType)
                                                    }
                                                    className="text-[#f53003] hover:underline dark:text-[#FF4433]"
                                                >
                                                    Edit
                                                </button>
                                            </td>
                                        </>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
