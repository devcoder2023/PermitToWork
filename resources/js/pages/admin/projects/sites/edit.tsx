import { Link, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import AppLayout from '@/components/layouts/app-layout';

type Project = {
    id: number;
    name: string;
};

type Site = {
    id: number;
    name: string;
    description: string | null;
};

type User = {
    id: number;
    name: string;
    email: string;
};

type Props = {
    project: Project;
    site: Site;
    availableManagers: User[];
    availableHseOfficers: User[];
    currentManagers: number[];
    currentHseOfficers: number[];
};

export default function EditSite({
    project,
    site,
    availableManagers,
    availableHseOfficers,
    currentManagers,
    currentHseOfficers,
}: Props) {
    const { data, setData, patch, processing, errors } = useForm({
        name: site.name,
        description: site.description ?? '',
        site_managers: currentManagers,
        hse_officers: currentHseOfficers,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(`/admin/sites/${site.id}`);
    };

    const toggleManager = (id: number) => {
        setData(
            'site_managers',
            data.site_managers.includes(id)
                ? data.site_managers.filter((m) => m !== id)
                : [...data.site_managers, id],
        );
    };

    const toggleHseOfficer = (id: number) => {
        setData(
            'hse_officers',
            data.hse_officers.includes(id)
                ? data.hse_officers.filter((h) => h !== id)
                : [...data.hse_officers, id],
        );
    };

    return (
        <AppLayout title={`Edit Site - ${project.name}`}>
            <div className="max-w-2xl">
                <div className="mb-6">
                    <Link
                        href={`/admin/projects/${project.id}/sites`}
                        className="text-sm text-[#f53003] hover:underline dark:text-[#FF4433]"
                    >
                        &larr; Back to Sites
                    </Link>
                </div>

                <h2 className="mb-6 text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                    Edit Site
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
                            htmlFor="description"
                            className="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Description
                        </label>
                        <textarea
                            id="description"
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                            rows={3}
                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-4 py-2.5 text-[#1b1b18] placeholder-[#706f6c] focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:placeholder-[#A1A09A] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                        />
                        {errors.description && (
                            <p className="mt-2 text-sm text-red-500">
                                {errors.description}
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                            Site Managers
                        </label>
                        <div className="max-h-48 overflow-y-auto rounded-md border border-[#e3e3e0] bg-white p-3 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]">
                            {availableManagers.length === 0 ? (
                                <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                    No available site managers.
                                </p>
                            ) : (
                                <div className="space-y-2">
                                    {availableManagers.map((manager) => (
                                        <label
                                            key={manager.id}
                                            className="flex cursor-pointer items-center rounded-md p-2 hover:bg-[#f5f5f4] dark:hover:bg-[#1a1a19]"
                                        >
                                            <input
                                                type="checkbox"
                                                checked={data.site_managers.includes(
                                                    manager.id,
                                                )}
                                                onChange={() =>
                                                    toggleManager(manager.id)
                                                }
                                                className="h-4 w-4 rounded border-[#e3e3e0] text-[#f53003] focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#FF4433] dark:focus:ring-[#FF4433]"
                                            />
                                            <span className="ml-2 text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                                                {manager.name} ({manager.email})
                                            </span>
                                        </label>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                            HSE Officers
                        </label>
                        <div className="max-h-48 overflow-y-auto rounded-md border border-[#e3e3e0] bg-white p-3 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]">
                            {availableHseOfficers.length === 0 ? (
                                <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                    No available HSE officers.
                                </p>
                            ) : (
                                <div className="space-y-2">
                                    {availableHseOfficers.map((officer) => (
                                        <label
                                            key={officer.id}
                                            className="flex cursor-pointer items-center rounded-md p-2 hover:bg-[#f5f5f4] dark:hover:bg-[#1a1a19]"
                                        >
                                            <input
                                                type="checkbox"
                                                checked={data.hse_officers.includes(
                                                    officer.id,
                                                )}
                                                onChange={() =>
                                                    toggleHseOfficer(officer.id)
                                                }
                                                className="h-4 w-4 rounded border-[#e3e3e0] text-[#f53003] focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#FF4433] dark:focus:ring-[#FF4433]"
                                            />
                                            <span className="ml-2 text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                                                {officer.name} ({officer.email})
                                            </span>
                                        </label>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="flex gap-4">
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-md bg-[#1b1b18] px-4 py-2.5 text-sm font-medium text-white hover:bg-black disabled:opacity-50 dark:bg-[#f53003] dark:hover:bg-[#d62a02] dark:disabled:bg-[#f53003]"
                        >
                            Update Site
                        </button>
                        <Link
                            href={`/admin/projects/${project.id}/sites`}
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
