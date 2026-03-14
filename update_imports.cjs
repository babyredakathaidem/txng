const fs = require('fs');
const path = require('path');

const files = [
  'resources/js/Pages/Auth/ConfirmPassword.vue',
  'resources/js/Pages/Auth/ForgotPassword.vue',
  'resources/js/Pages/Auth/Login.vue',
  'resources/js/Pages/Auth/Register.vue',
  'resources/js/Pages/Auth/ResetPassword.vue',
  'resources/js/Pages/Auth/VerifyEmail.vue',
  'resources/js/Pages/Auth/VerifyEmailSuccess.vue',
  'resources/js/Pages/Batches/Index.vue',
  'resources/js/Pages/Batches/Lineage.vue',
  'resources/js/Pages/Batches/Merge.vue',
  'resources/js/Pages/Batches/Qrs.vue',
  'resources/js/Pages/Batches/Split.vue',
  'resources/js/Pages/Batches/Transform.vue',
  'resources/js/Pages/Dashboard.vue',
  'resources/js/Pages/Enterprise/Certificates/Index.vue',
  'resources/js/Pages/Enterprise/Settings.vue',
  'resources/js/Pages/Enterprise/Users/Index.vue',
  'resources/js/Pages/Events/CreateObservation.vue',
  'resources/js/Pages/Events/CreateTransferIn.vue',
  'resources/js/Pages/Events/CreateTransferOut.vue',
  'resources/js/Pages/Events/CreateTransformation.vue',
  'resources/js/Pages/Events/Index.vue',
  'resources/js/Pages/Events/Tranfer/Pending.vue',
  'resources/js/Pages/Onboarding/EnterpriseBlocked.vue',
  'resources/js/Pages/Onboarding/EnterpriseCreate.vue',
  'resources/js/Pages/Onboarding/EnterprisePending.vue',
  'resources/js/Pages/Onboarding/EnterpriseRejected.vue',
  'resources/js/Pages/Products/Index.vue',
  'resources/js/Pages/Products/Show.vue',
  'resources/js/Pages/Profile/Edit.vue',
  'resources/js/Pages/Public/Categories.vue',
  'resources/js/Pages/Public/Home.vue',
  'resources/js/Pages/Public/Products.vue',
  'resources/js/Pages/Public/Verify.vue',
  'resources/js/Pages/Sys/Config/Index.vue',
  'resources/js/Pages/Sys/EnterpriseShow.vue',
  'resources/js/Pages/Sys/Enterprises.vue',
  'resources/js/Pages/Sys/Stats.vue',
  'resources/js/Pages/TraceLocation/Index.vue',
  'resources/js/Pages/Trace/Blocked.vue',
  'resources/js/Pages/Trace/GatePrivate.vue',
  'resources/js/Pages/Trace/GatePublic.vue',
  'resources/js/Pages/Trace/Show.vue',
  'resources/js/Pages/Trace/StepShow.vue',
  'resources/js/Pages/Welcome.vue'
];

files.forEach(file => {
  const filePath = path.resolve('D:/XAMpp/htdocs/traceability', file);
  if (!fs.existsSync(filePath)) {
    console.error(`File not found: ${filePath}`);
    return;
  }
  let content = fs.readFileSync(filePath, 'utf8');
  let originalContent = content;

  // 1. Remove ScrollArea import
  // Handles: import { ScrollArea } from '@/components/ui/scroll-area'
  content = content.replace(/import\s+{\s*ScrollArea\s*}\s+from\s+'@\/components\/ui\/scroll-area'[\r\n]*/g, '');

  // 2. Update imports to point to index.js
  // Matches: from '@/components/ui/card' or from '@/components/ui/card'
  // Excludes already having /index.js or .vue
  content = content.replace(/from\s+'@\/components\/ui\/([^'/]+)'/g, (match, p1) => {
    return `from '@/components/ui/${p1}/index.js'`;
  });

  // 3. Replace <ScrollArea> tags
  content = content.replace(/<ScrollArea(\s+[^>]*)?>/g, (match, attrs) => {
    attrs = attrs || '';
    if (attrs.match(/class=["']/)) {
       // Append to existing class
       return `<div${attrs.replace(/class=(["'])/, 'class=$1overflow-y-auto ')}>`; 
    } else {
       // Add new class
       return `<div class="overflow-y-auto"${attrs}>`;
    }
  });
  
  // 4. Replace closing tag
  content = content.replace(/<\/ScrollArea>/g, '</div>');

  if (content !== originalContent) {
    fs.writeFileSync(filePath, content, 'utf8');
    console.log(`Updated ${file}`);
  } else {
    console.log(`No changes for ${file}`);
  }
});
