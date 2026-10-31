import "../css/app.css";
import { createInertiaApp } from "@inertiajs/react";
import { createRoot } from "react-dom/client";
import React from "react";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";

export const D1_BRIDGE_URL =
    "https://pombensin-d1-bridge.fyosuaphabeahan3.workers.dev";

/**
 * Frontend-only D1 bridge helper. The bridge secret remains server-side and
 * must never be exposed in this bundle.
 */
export async function fetchD1(path, options = {}) {
    const response = await fetch(`${D1_BRIDGE_URL}${path}`, {
        ...options,
        headers: {
            Accept: "application/json",
            ...(options.headers ?? {}),
        },
    });

    if (!response.ok) {
        throw new Error(`D1 bridge request failed (${response.status})`);
    }

    return response.json();
}

function CashierPage({ auth = null }) {
    return React.createElement(
        "main",
        { className: "min-h-screen bg-slate-50 p-6 text-slate-900" },
        React.createElement(
            "section",
            {
                className:
                    "mx-auto max-w-5xl rounded-xl bg-white p-8 shadow-sm",
            },
            React.createElement(
                "h1",
                { className: "text-2xl font-semibold" },
                "POS Pertamini",
            ),
            React.createElement(
                "p",
                { className: "mt-2 text-slate-600" },
                `Halaman kasir siap digunakan${auth?.user?.name ? `, ${auth.user.name}` : ""}.`,
            ),
        ),
    );
}

const pages = {
    Cashier: CashierPage,
    Kasir: CashierPage,
    "jual.index": CashierPage,
};

const appElement = document.getElementById("app");

if (!appElement) {
    throw new Error("Elemen #app tidak ditemukan.");
}

// PHP normally supplies data-page. Static Pages deployments do not, so seed
// the same shape before createInertiaApp reads it.
if (!appElement.dataset.page) {
    appElement.dataset.page = JSON.stringify({
        component: "Cashier",
        props: { auth: null, bridgeUrl: D1_BRIDGE_URL },
        url: window.location.pathname,
        version: null,
    });
}

createInertiaApp({
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob("./Pages/**/*.jsx"),
        ),
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});
