import { Link, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import AppLayout from '@/components/layouts/app-layout';

type SubContractor = {
    id: number;
    name: string;
    contact_email: string;
    contact_phone: string | null;
    address: string | null;
    is_active: boolean;
};

type Props = {
    subContractor: SubContractor;
};

export default function EditSubContractor({ subContractor }: Props) {
    const { data, setData, patch, processing, errors } = useForm({
        name: subContractor.name,
        contact_email: subContractor.contact_email,
        contact_phone: subContractor.contact_phone ?? '',
        address: subContractor.address ?? '',
        is_active: subContractor.is_active,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(`/admin/sub-contractors/${subContractor.id}`);
    };

    return (
        <AppLayout title="Edit Sub-Contractor">
            <div className="max-w-2xl">
                <div className="mb-6">
                    <Link
                        href="/admin/sub-contractors"
                        className="text-sm text-[#f53003] hover:underline dark:text-[#FF4433]"
                    >
                        &larr; Back to Sub-Contractors
                    </Link>
                </div>

                <h2 className="mb-6 text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                    Edit Sub-Contractor
                </h2>

                <form onSubmit={submit} className="space-y-6">
                    <div>
                        <label
                            htmlFor="name"
                            className="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Name <span className="text-red-500">*</span>
                        </label>
                        <input
                            id="name"
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-4 py-2.5 text-[#1b1b18] placeholder-[#706f6c] focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:placeholder-[#A1A09A] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                        />
                        {errors.name && (
                            <p className="mt-2 text-sm text-red-500">
                                {errors.name}
                            </p>
                        )}
                    </div>

                    <div>
                        <label
                            htmlFor="contact_email"
                            className="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Contact Email{' '}
                            <span className="text-red-500">*</span>
                        </label>
                        <input
                            id="contact_email"
                            type="email"
                            value={data.contact_email}
                            onChange={(e) =>
                                setData('contact_email', e.target.value)
                            }
                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-4 py-2.5 text-[#1b1b18] placeholder-[#706f6c] focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:placeholder-[#A1A09A] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                        />
                        {errors.contact_email && (
                            <p className="mt-2 text-sm text-red-500">
                                {errors.contact_email}
                            </p>
                        )}
                    </div>

                    <div>
                        <label
                            htmlFor="contact_phone"
                            className="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Contact Phone
                        </label>
                        <input
                            id="contact_phone"
                            type="text"
                            value={data.contact_phone}
                            onChange={(e) =>
                                setData('contact_phone', e.target.value)
                            }
                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-4 py-2.5 text-[#1b1b18] placeholder-[#706f6c] focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:placeholder-[#A1A09A] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                        />
                        {errors.contact_phone && (
                            <p className="mt-2 text-sm text-red-500">
                                {errors.contact_phone}
                            </p>
                        )}
                    </div>

                    <div>
                        <label
                            htmlFor="address"
                            className="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Address
                        </label>
                        <textarea
                            id="address"
                            value={data.address}
                            onChange={(e) => setData('address', e.target.value)}
                            rows={3}
                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-4 py-2.5 text-[#1b1b18] placeholder-[#706f6c] focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:placeholder-[#A1A09A] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                        />
                        {errors.address && (
                            <p className="mt-2 text-sm text-red-500">
                                {errors.address}
                            </p>
                        )}
                    </div>

                    <div className="flex items-center">
                        <input
                            id="is_active"
                            type="checkbox"
                            checked={data.is_active}
                            onChange={(e) =>
                                setData('is_active', e.target.checked)
                            }
                            className="h-4 w-4 rounded border-[#e3e3e0] text-[#f53003] focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#FF4433] dark:focus:ring-[#FF4433]"
                        />
                        <label
                            htmlFor="is_active"
                            className="ml-2 text-sm text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Active
                        </label>
                    </div>

                    <div className="flex gap-4">
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-md bg-[#1b1b18] px-4 py-2.5 text-sm font-medium text-white hover:bg-black disabled:opacity-50 dark:bg-[#f53003] dark:hover:bg-[#d62a02] dark:disabled:bg-[#f53003]"
                        >
                            Update Sub-Contractor
                        </button>
                        <Link
                            href="/admin/sub-contractors"
                            className="rounded-md border border-[#e3e3e0] bg-white px-4 py-2.5 text-sm font-medium text-[#1b1b18] hover:bg-[#f5f5f4] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#1a1a19]"
                        >
                            Cancel
                        </Link>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
