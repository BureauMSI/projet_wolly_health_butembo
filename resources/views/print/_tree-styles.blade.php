<style>
.print-tree-wrap { overflow: visible; }
.mlm-tree,
.mlm-tree ul { display: flex; justify-content: center; list-style: none; margin: 0; }
.mlm-tree { width: max-content; min-width: 100%; padding: 8px 4px 12px; }
.mlm-tree ul { padding: 14px 0 0; position: relative; }
.mlm-tree ul::before { content: ""; position: absolute; top: 0; left: 50%; height: 14px; border-left: 1px solid #000; }
.mlm-tree li { display: flex; flex-direction: column; align-items: center; position: relative; padding: 14px 4px 0; }
.mlm-tree li::before,
.mlm-tree li::after { content: ""; position: absolute; top: 0; width: 50%; height: 14px; border-top: 1px solid #000; }
.mlm-tree li::before { right: 50%; }
.mlm-tree li::after { left: 50%; border-left: 1px solid #000; }
.mlm-tree li:only-child::before,
.mlm-tree li:only-child::after { display: none; }
.mlm-tree li:first-child::before,
.mlm-tree li:last-child::after { border: 0 none; }
.mlm-tree li:last-child::before { border-right: 1px solid #000; }
.mlm-tree > li { padding-top: 0; }
.mlm-tree > li::before,
.mlm-tree > li::after { display: none; }
.mlm-eq-link-near::before,
.mlm-eq-link-near::after,
.mlm-tree ul:has(> .mlm-eq-link-near)::before { border-color: #1f8f55 !important; border-width: 2px !important; }
.mlm-eq-link-far::before,
.mlm-eq-link-far::after,
.mlm-tree ul:has(> .mlm-eq-link-far)::before { border-color: #d97706 !important; border-width: 2px !important; }
.mlm-eq-badge { display: block; font-size: 8px; font-weight: 700; margin-bottom: 2px; }
.mlm-eq-badge-near { color: #0d4d2f; }
.mlm-eq-badge-far { color: #7a4a00; }
.mlm-node-eq { border-color: #1f8f55; }
.mlm-node { width: 28mm; background: #fff; border: 1px solid #000; padding: 4px 3px; text-align: center; position: relative; z-index: 1; }
.mlm-node-self { background: #edf7ed; }
.mlm-node-empty { border-style: dashed; color: #444; }
.mlm-chip { display: block; font-size: 8px; font-weight: 700; text-transform: uppercase; margin-bottom: 2px; }
.mlm-avatar { display: none; }
.mlm-name { font-size: 10px; font-weight: 700; line-height: 1.2; }
.mlm-meta { font-size: 9px; }
.mlm-feet { display: grid; grid-template-columns: 1fr 1fr; gap: 2px; margin-top: 4px; }
.mlm-foot { border: 1px solid #000; padding: 2px; }
.mlm-foot span { display: block; font-size: 8px; }
.mlm-foot strong { display: block; font-size: 10px; }
.mlm-node-empty .mlm-feet { display: none; }
@media print {
    .print-tree-wrap { overflow: visible; }
}
</style>
