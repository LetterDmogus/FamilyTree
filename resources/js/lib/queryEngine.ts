/**
 * Query Engine for Family Tree
 * Based on ReAct Concept in @FEATURE.md
 */

export interface FamilyMember {
    id: number;
    panggilan: string;
    full_name: string;
    photo_url?: string;
    gender: 'M' | 'F';
    is_alive: boolean;
    birth_date?: string;
    death_date?: string;
    pekerjaan?: string;
    is_admin: boolean;
    depth?: number;
    spouse?: FamilyMember[];
    children?: FamilyMember[];
}

export interface QueryOperation {
    op: 'filter' | 'sort' | 'select' | 'aggregate' | 'traverse';
    field?: string;
    value?: any;
    order?: 'asc' | 'desc';
    limit?: number;
    relation?: string;
    id?: number;
    type?: 'ancestors' | 'descendants' | 'path';
    depth?: number;
}

export function flattenTree(node: FamilyMember, members: Map<number, FamilyMember> = new Map()): FamilyMember[] {
    if (!node || members.has(node.id)) return [];

    const member = { ...node };
    members.set(node.id, member);

    if (node.spouse) {
        node.spouse.forEach(s => flattenTree(s, members));
    }

    if (node.children) {
        node.children.forEach(c => flattenTree(c, members));
    }

    return Array.from(members.values());
}

export function queryFamily(treeData: FamilyMember, operations: QueryOperation[]) {
    let result: any = flattenTree(treeData);

    for (const op of operations) {
        if (op.op === 'filter') {
            result = applyFilter(result, op);
        } else if (op.op === 'sort') {
            result = applySort(result, op);
        } else if (op.op === 'select') {
            result = applySelect(result, op);
        } else if (op.op === 'aggregate') {
            return applyAggregate(result, op);
        } else if (op.op === 'traverse') {
            result = applyTraverse(treeData, op);
        }
    }

    return result;
}

function applyFilter(data: FamilyMember[], op: QueryOperation) {
    if (op.field && op.value !== undefined) {
        return data.filter(item => {
            const val = item[op.field as keyof FamilyMember];
            if (typeof val === 'string' && typeof op.value === 'string') {
                return val.toLowerCase().includes(op.value.toLowerCase());
            }
            return val === op.value;
        });
    }
    
    if (op.relation) {
        // Simple relation filter based on 'panggilan' or depth if needed
        // But usually AI will use field filters for gender, is_alive, etc.
        if (op.relation === 'cucu') {
             // Cucu are usually depth 3 if root is depth 1
             return data.filter(item => item.depth === 3);
        }
        if (op.relation === 'anak') {
            return data.filter(item => item.depth === 2);
        }
    }

    return data;
}

function applySort(data: FamilyMember[], op: QueryOperation) {
    if (!op.field) return data;

    return [...data].sort((a, b) => {
        const valA = a[op.field as keyof FamilyMember] ?? '';
        const valB = b[op.field as keyof FamilyMember] ?? '';

        if (valA < valB) return op.order === 'desc' ? 1 : -1;
        if (valA > valB) return op.order === 'desc' ? -1 : 1;
        return 0;
    });
}

function applySelect(data: FamilyMember[], op: QueryOperation) {
    let result = data;
    if (op.limit) {
        result = result.slice(0, op.limit);
    }
    // We keep important fields for AI context
    return result.map(item => ({
        id: item.id,
        panggilan: item.panggilan,
        full_name: item.full_name,
        gender: item.gender,
        birth_date: item.birth_date,
        is_alive: item.is_alive,
        pekerjaan: item.pekerjaan
    }));
}

function applyAggregate(data: FamilyMember[], op: QueryOperation) {
    if (op.field === 'count') {
        return { count: data.length };
    }
    return { result: data.length };
}

function applyTraverse(root: FamilyMember, op: QueryOperation) {
    // Basic traverse: find node and return its context
    const all = flattenTree(root);
    const target = all.find(m => m.id === op.id);
    if (!target) return [];

    if (op.type === 'descendants') {
        return flattenTree(target).filter(m => m.id !== target.id);
    }

    // For simplicity, traverse returns the targeted member with their immediate spouse/children
    return [{
        id: target.id,
        panggilan: target.panggilan,
        full_name: target.full_name,
        spouse: target.spouse?.map(s => s.panggilan),
        children: target.children?.map(c => c.panggilan)
    }];
}

export function getSummary(treeData: FamilyMember) {
    const all = flattenTree(treeData);
    return {
        total_members: all.length,
        alive_members: all.filter(m => m.is_alive).length,
        deceased_members: all.filter(m => !m.is_alive).length,
        generations: Math.max(...all.map(m => m.depth || 0)),
        male_count: all.filter(m => m.gender === 'M').length,
        female_count: all.filter(m => m.gender === 'F').length
    };
}
