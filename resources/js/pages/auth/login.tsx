import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import { home } from '@/routes';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <>
            <Head title="Login">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600"
                    rel="stylesheet"
                />
            </Head>
            <div className="flex min-h-screen flex-col items-center justify-center bg-[#FDFDFC] p-6 dark:bg-[#0a0a0a]">
                <div className="w-full max-w-md">
                    <div className="mb-8 text-center">
                        <h1 className="text-2xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                            Permit To Work
                        </h1>
                        <p className="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Sign in to your account
                        </p>
                    </div>

                    <form
                        onSubmit={submit}
                        className="rounded-lg bg-white p-8 shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                    >
                        <div className="mb-4">
                            <label
                                htmlFor="email"
                                className="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                Email
                            </label>
                            <input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) =>
                                    setData('email', e.target.value)
                                }
                                className="w-full rounded-md border border-[#e3e3e0] bg-white px-4 py-2.5 text-[#1b1b18] placeholder-[#706f6c] focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:placeholder-[#A1A09A] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                                placeholder="email@example.com"
                                autoComplete="email"
                            />
                            {errors.email && (
                                <p className="mt-2 text-sm text-red-500">
                                    {errors.email}
                                </p>
                            )}
                        </div>

                        <div className="mb-4">
                            <label
                                htmlFor="password"
                                className="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                Password
                            </label>
                            <input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(e) =>
                                    setData('password', e.target.value)
                                }
                                className="w-full rounded-md border border-[#e3e3e0] bg-white px-4 py-2.5 text-[#1b1b18] placeholder-[#706f6c] focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:placeholder-[#A1A09A] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                                placeholder="password"
                                autoComplete="current-password"
                            />
                            {errors.password && (
                                <p className="mt-2 text-sm text-red-500">
                                    {errors.password}
                                </p>
                            )}
                        </div>

                        <div className="mb-6 flex items-center">
                            <input
                                id="remember"
                                type="checkbox"
                                checked={data.remember}
                                onChange={(e) =>
                                    setData('remember', e.target.checked)
                                }
                                className="h-4 w-4 rounded border-[#e3e3e0] text-[#f53003] focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#FF4433] dark:focus:ring-[#FF4433]"
                            />
                            <label
                                htmlFor="remember"
                                className="ml-2 text-sm text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Remember me
                            </label>
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-md bg-[#1b1b18] px-4 py-2.5 text-sm font-medium text-white hover:bg-black disabled:opacity-50 dark:bg-[#f53003] dark:hover:bg-[#d62a02] dark:disabled:bg-[#f53003]"
                        >
                            Sign in
                        </button>
                    </form>

                    <p className="mt-6 text-center text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        <Link
                            href={home.url()}
                            className="text-[#f53003] hover:underline dark:text-[#FF4433]"
                        >
                            Back to home
                        </Link>
                    </p>
                </div>
            </div>
        </>
    );
}
