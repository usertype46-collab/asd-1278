// Cloudflare Edge Route: /api/therapists
export async function onRequestGet(context) {
    const { env } = context;
    try {
        const { results } = await env.DB.prepare("SELECT * FROM therapists ORDER BY id DESC").all();
        
        const formatted = results.map(t => ({
            ...t,
            skills: JSON.parse(t.skills || '[]')
        }));

        return new Response(JSON.stringify(formatted), {
            headers: { "Content-Type": "application/json" }
        });
    } catch (err) {
        return new Response(JSON.stringify({ error: err.message }), { status: 500 });
    }
}

export async function onRequestPost(context) {
    const { request, env } = context;
    const body = await request.json();
    const { name, phone, gender, skills } = body;

    try {
        // 自動遞增編號邏輯
        const maxIdResult = await env.DB.prepare("SELECT MAX(id) as maxId FROM therapists").first();
        const nextId = (maxIdResult.maxId || 0) + 1;
        const code = `T${String(nextId).padStart(3, '0')}`; // 產生如 T001, T002 格式

        await env.DB.prepare(
            "INSERT INTO therapists (code, name, phone, gender, skills) VALUES (?, ?, ?, ?, ?)"
        ).bind(code, name, phone, gender || 'M', JSON.stringify(skills)).run();

        return new Response(JSON.stringify({ success: true, code }), { status: 201 });
    } catch (err) {
        return new Response(JSON.stringify({ error: err.message }), { status: 500 });
    }
}

export async function onRequestDelete(context) {
    const { request, env } = context;
    const url = new URL(request.url);
    const id = url.searchParams.get('id');

    try {
        // 永久刪除離職技師
        await env.DB.prepare("DELETE FROM therapists WHERE id = ?").bind(id).run();
        return new Response(JSON.stringify({ success: true }), { status: 200 });
    } catch (err) {
        return new Response(JSON.stringify({ error: err.message }), { status: 500 });
    }
}
