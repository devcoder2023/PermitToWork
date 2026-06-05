import { Link, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import AppLayout from '@/components/layouts/app-layout';

type User = {
    id: number;
    name: string;
    email: string;
    role: string;
    project_id: number | null;
    sub_contractor_id: number | null;
    phone: string | null;
};

type Project = {
    id: number;
    name: string;
};

type SubContractor = {
    id: number;
    name: string;
};

type Role = {
    value: string;
    label: string;
};

type Props = {
    user: User;
    projects: Project[];
    subContractors: SubContractor[];
    roles: Role[];
};

export default function EditUser({
    user,
    projects,
    subContractors,
    roles,
}: Props) {
    const { data, setData, patch, processing, errors } = useForm({
        name: user.name,
        email: user.email,
        password: '',
        password_confirmation: '',
        role: user.role,
        project_id: user.project_id?.toString() ?? '',
        sub_contractor_id: user.sub_contractor_id?.toString() ?? '',
        phone: user.phone ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(`/admin/users/${user.id}`);
    };

    const rolesRequiringProject = [
        'execution_engineer',
        'site_manager',
        'permit_officer',
        'work_supervisor',
        'hse_officer',
        'consultant',
    ];

    const rolesRequiringSubContractor = [
        'execution_engineer',
        'work_supervisor',
    ];

    return (
        <AppLayout title="Edit User">
            <div className="max-w-2xl">
                <div className="mb-6">
                    <Link
                        href="/admin/users"
                        className="text-sm text-[#f53003] hover:underline dark:text-[#FF4433]"
                    >
                        &larr; Back to Users
                    </Link>
                </div>

                <h2 className="mb-6 text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                    Edit User
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
                            htmlFor="email"
                            className="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Email <span className="text-red-500">*</span>
                        </label>
                        <input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-4 py-2.5 text-[#1b1b18] placeholder-[#706f6c] focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:placeholder-[#A1A09A] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                        />
                        {errors.email && (
                            <p className="mt-2 text-sm text-red-500">
                                {errors.email}
                            </p>
                        )}
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                htmlFor="password"
                                className="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                Password (leave empty to keep current)
                            </label>
                            <input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(e) =>
                                    setData('password', e.target.value)
                                }
                                className="w-full rounded-md border border-[#e3e3e0] bg-white px-4 py-2.5 text-[#1b1b18] focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                            />
                            {errors.password && (
                                <p className="mt-2 text-sm text-red-500">
                                    {errors.password}
                                </p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="password_confirmation"
                                className="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                Confirm Password
                            </label>
                            <input
                                id="password_confirmation"
                                type="password"
                                value={data.password_confirmation}
                                onChange={(e) =>
                                    setData(
                                        'password_confirmation',
                                        e.target.value,
                                    )
                                }
                                className="w-full rounded-md border border-[#e3e3e0] bg-white px-4 py-2.5 text-[#1b1b18] focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                            />
                        </div>
                    </div>

                    <div>
                        <label
                            htmlFor="role"
                            className="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Role <span className="text-red-500">*</span>
                        </label>
                        <select
                            id="role"
                            value={data.role}
                            onChange={(e) => setData('role', e.target.value)}
                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-4 py-2.5 text-[#1b1b18] focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                        >
                            {roles.map((role) => (
                                <option key={role.value} value={role.value}>
                                    {role.label}
                                </option>
                            ))}
                        </select>
                        {errors.role && (
                            <p className="mt-2 text-sm text-red-500">
                                {errors.role}
                            </p>
                        )}
                    </div>

                    {rolesRequiringProject.includes(data.role) && (
                        <div>
                            <label
                                htmlFor="project_id"
                                className="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                Project
                            </label>
                            <select
                                id="project_id"
                                value={data.project_id}
                                onChange={(e) =>
                                    setData('project_id', e.target.value)
                                }
                                className="w-full rounded-md border border-[#e3e3e0] bg-white px-4 py-2.5 text-[#1b1b18] focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                            >
                                <option value="">Select a project</option>
                                {projects.map((project) => (
                                    <option key={project.id} value={project.id}>
                                        {project.name}
                                    </option>
                                ))}
                            </select>
                            {errors.project_id && (
                                <p className="mt-2 text-sm text-red-500">
                                    {errors.project_id}
                                </p>
                            )}
                        </div>
                    )}

                    {rolesRequiringSubContractor.includes(data.role) && (
                        <div>
                            <label
                                htmlFor="sub_contractor_id"
                                className="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                Sub-Contractor (leave empty for main contractor)
                            </label>
                            <select
                                id="sub_contractor_id"
                                value={data.sub_contractor_id}
                                onChange={(e) =>
                                    setData('sub_contractor_id', e.target.value)
                                }
                                className="w-full rounded-md border border-[#e3e3e0] bg-white px-4 py-2.5 text-[#1b1b18] focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                            >
                                <option value="">Main Contractor</option>
                                {subContractors.map((sc) => (
                                    <option key={sc.id} value={sc.id}>
                                        {sc.name}
                                    </option>
                                ))}
                            </select>
                            {errors.sub_contractor_id && (
                                <p className="mt-2 text-sm text-red-500">
                                    {errors.sub_contractor_id}
                                </p>
                            )}
                        </div>
                    )}

                    <div>
                        <label
                            htmlFor="phone"
                            className="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Phone
                        </label>
                        <input
                            id="phone"
                            type="text"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-4 py-2.5 text-[#1b1b18] placeholder-[#706f6c] focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:placeholder-[#A1A09A] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                        />
                        {errors.phone && (
                            <p className="mt-2 text-sm text-red-500">
                                {errors.phone}
                            </p>
                        )}
                    </div>

                    <div className="flex gap-4">
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-md bg-[#1b1b18] px-4 py-2.5 text-sm font-medium text-white hover:bg-black disabled:opacity-50 dark:bg-[#f53003] dark:hover:bg-[#d62a02] dark:disabled:bg-[#f53003]"
                        >
                            Update User
                        </button>
                        <Link
                            href="/admin/users"
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
