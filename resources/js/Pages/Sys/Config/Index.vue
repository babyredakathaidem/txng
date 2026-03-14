<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Badge } from '@/Components/ui/badge/index.js'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/Components/ui/table/index.js'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog/index.js'
import { Input } from '@/Components/ui/input/index.js'
import { Label } from '@/Components/ui/label/index.js'
import { Checkbox } from '@/Components/ui/checkbox/index.js'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/Components/ui/tabs/index.js'
import { 
  Settings2, 
  Plus, 
  Layers, 
  ClipboardList, 
  CheckCircle, 
  Circle, 
  Pencil, 
  Info,
  Activity,
  ArrowRight
} from 'lucide-vue-next'

const props = defineProps({
    categories: Array,
    cte_templates: Array,
})

// ── Tab ───────────────────────────────────────────────────
const activeTab = ref('cte')

// ── Filter CTE theo category ──────────────────────────────
const filterCatId = ref(null)
const filteredCte = computed(() => {
    if (!filterCatId.value) return props.cte_templates
    return props.cte_templates.filter(t => t.category_id === filterCatId.value)
})

// ── Thêm category mới ─────────────────────────────────────
const showAddCat = ref(false)
const catForm = useForm({ code: '', name_vi: '', tcvn_ref: '' })
const submitCat = () => {
    catForm.post(route('sys.config.categories.store'), {
        onSuccess: () => { showAddCat.value = false; catForm.reset() },
    })
}

// ── Chỉnh sửa CTE template ────────────────────────────────
const editingCte = ref(null)
const cteForm = useForm({ name_vi: '', is_required: false, tcvn_note: '' })

function openEditCte(t) {
    editingCte.value = t
    cteForm.name_vi = t.name_vi
    cteForm.is_required = !!t.is_required
    cteForm.tcvn_note = t.tcvn_note ?? ''
}

function submitEditCte() {
    cteForm.put(route('sys.config.cte.update', editingCte.value.id), {
        onSuccess: () => { editingCte.value = null },
    })
}

// ── Helpers ───────────────────────────────────────────────
const catName = (id) => props.categories.find(c => c.id === id)?.name_vi ?? '—'

const cteHeaders = [
    { key: 'step', label: 'Bước' },
    { key: 'code', label: 'CTE Code' },
    { key: 'name', label: 'Tên sự kiện' },
    { key: 'category', label: 'Danh mục' },
    { key: 'required', label: 'Bắt buộc' },
    { key: 'tcvn', label: 'TCVN' },
    { key: 'actions', label: '' },
]

const catHeaders = [
    { key: 'code', label: 'Code' },
    { key: 'name', label: 'Tên danh mục' },
    { key: 'tcvn', label: 'TCVN tham chiếu' },
    { key: 'batches', label: 'Số lô' },
]
</script>

<template>
    <Head title="Cấu hình CTE / TCVN" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
          <div>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-primary mb-1">System Engineering</p>
            <h1 class="text-4xl font-black tracking-tighter text-foreground">Cấu hình CTE / TCVN</h1>
            <p class="text-muted-foreground font-medium text-sm mt-1 opacity-70">Quản lý danh mục sản phẩm và các bước truy xuất chuẩn quốc gia.</p>
          </div>
          <Button @click="showAddCat = true" class="shadow-xl shadow-primary/20">
            <Plus class="w-4 h-4 mr-2" /> Thêm danh mục
          </Button>
        </div>

        <Tabs v-model="activeTab" class="w-full">
          <TabsList class="p-1 bg-muted/30 rounded-full w-fit border border-border/50 mb-8">
            <TabsTrigger value="cte" class="rounded-full px-6 text-[10px] font-black uppercase tracking-widest data-[state=active]:bg-primary data-[state=active]:text-primary-foreground transition-all duration-300">CTE Templates</TabsTrigger>
            <TabsTrigger value="cat" class="rounded-full px-6 text-[10px] font-black uppercase tracking-widest data-[state=active]:bg-primary data-[state=active]:text-primary-foreground transition-all duration-300">Danh mục sản phẩm</TabsTrigger>
          </TabsList>

          <TabsContent value="cte" class="space-y-6 mt-0">
            <!-- Filter theo category -->
            <div class="flex gap-2 flex-wrap bg-card/50 backdrop-blur-sm p-2 rounded-2xl border border-border/50 shadow-sm">
                <button 
                    @click="filterCatId = null"
                    class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all duration-300"
                    :class="!filterCatId ? 'bg-primary text-primary-foreground shadow-lg shadow-primary/20 scale-105' : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground'"
                >
                    Tất cả ({{ cte_templates.length }})
                </button>
                <button 
                    v-for="c in categories" 
                    :key="c.id" 
                    @click="filterCatId = c.id"
                    class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all duration-300"
                    :class="filterCatId === c.id ? 'bg-primary text-primary-foreground shadow-lg shadow-primary/20 scale-105' : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground'"
                >
                    {{ c.name_vi }} ({{cte_templates.filter(t => t.category_id === c.id).length}})
                </button>
            </div>

            <Card class="border-border/50 bg-card/50 backdrop-blur-sm overflow-hidden">
                <CardContent class="p-0">
                  <Table>
                    <TableHeader class="bg-muted/50">
                      <TableRow>
                        <TableHead v-for="h in cteHeaders" :key="h.key" class="text-[10px] font-black uppercase tracking-widest">{{ h.label }}</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      <TableRow v-for="t in filteredCte" :key="t.id" class="group hover:bg-muted/30 transition-colors">
                        <TableCell class="font-mono text-[10px] font-black text-muted-foreground/40 ">STEP_{{ String(t.step_order).padStart(2, '0') }}</TableCell>
                        <TableCell>
                          <Badge variant="outline" class="font-mono text-[10px] font-black tracking-tighter bg-background border-primary/20 text-primary uppercase">{{ t.code }}</Badge>
                        </TableCell>
                        <TableCell class="font-black text-foreground tracking-tight">{{ t.name_vi }}</TableCell>
                        <TableCell>
                          <span class="text-[10px] font-black uppercase tracking-widest text-muted-foreground/60">{{ catName(t.category_id) }}</span>
                        </TableCell>
                        <TableCell>
                          <Badge :variant="t.is_required ? 'default' : 'secondary'" class="font-black text-[9px] uppercase tracking-widest">
                            {{ t.is_required ? 'Bắt buộc' : 'Tùy chọn' }}
                          </Badge>
                        </TableCell>
                        <TableCell>
                          <span class="text-[10px] font-bold text-muted-foreground/60 ">{{ t.tcvn_note ?? '—' }}</span>
                        </TableCell>
                        <TableCell class="text-right">
                          <Button variant="ghost" size="icon" @click="openEditCte(t)" class="h-8 w-8 hover:bg-blue-500/10 hover:text-blue-500 transition-colors">
                            <Pencil class="w-4 h-4" />
                          </Button>
                        </TableCell>
                      </TableRow>
                    </TableBody>
                  </Table>
                </CardContent>
            </Card>
          </TabsContent>

          <TabsContent value="cat" class="mt-0">
            <Card class="border-border/50 bg-card/50 backdrop-blur-sm overflow-hidden">
                <CardContent class="p-0">
                  <Table>
                    <TableHeader class="bg-muted/50">
                      <TableRow>
                        <TableHead v-for="h in catHeaders" :key="h.key" class="text-[10px] font-black uppercase tracking-widest">{{ h.label }}</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      <TableRow v-for="c in categories" :key="c.id" class="group hover:bg-muted/30 transition-colors">
                        <TableCell>
                          <Badge variant="outline" class="font-mono text-[10px] font-black tracking-tighter bg-background border-primary/20 text-primary uppercase">{{ c.code }}</Badge>
                        </TableCell>
                        <TableCell class="font-black text-foreground tracking-tight text-base">{{ c.name_vi }}</TableCell>
                        <TableCell>
                          <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-muted-foreground/60">
                            <Layers class="w-3 h-3 opacity-40" />
                            {{ c.tcvn_ref ?? '—' }}
                          </div>
                        </TableCell>
                        <TableCell>
                          <div class="flex items-center gap-2">
                            <span class="text-sm font-black text-primary">{{ c.batches_count ?? 0 }}</span>
                            <span class="text-[10px] font-bold text-muted-foreground/40 uppercase tracking-widest">batches</span>
                          </div>
                        </TableCell>
                      </TableRow>
                    </TableBody>
                  </Table>
                </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
    </div>


    <!-- Dialog thêm danh mục -->
    <Dialog :open="showAddCat" @update:open="(v) => (!v && (showAddCat = false))">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Thêm danh mục sản phẩm</DialogTitle>
          <DialogDescription>Tạo danh mục mới để phân nhóm các quy trình truy xuất.</DialogDescription>
        </DialogHeader>
        <div class="grid gap-4 py-4">
          <div class="space-y-2">
            <Label for="catCode">Code (VD: lua_gao)</Label>
            <Input id="catCode" v-model="catForm.code" placeholder="lua_gao" />
            <p v-if="catForm.errors.code" class="text-xs text-destructive">{{ catForm.errors.code }}</p>
          </div>
          <div class="space-y-2">
            <Label for="catName">Tên tiếng Việt</Label>
            <Input id="catName" v-model="catForm.name_vi" placeholder="Lúa gạo" />
            <p v-if="catForm.errors.name_vi" class="text-xs text-destructive">{{ catForm.errors.name_vi }}</p>
          </div>
          <div class="space-y-2">
            <Label for="catTcvn">TCVN tham chiếu</Label>
            <Input id="catTcvn" v-model="catForm.tcvn_ref" placeholder="TCVN 12850:2019" />
            <p v-if="catForm.errors.tcvn_ref" class="text-xs text-destructive">{{ catForm.errors.tcvn_ref }}</p>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="showAddCat = false">Hủy</Button>
          <Button @click="submitCat" :disabled="catForm.processing">Tạo danh mục</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Dialog sửa CTE -->
    <Dialog :open="!!editingCte" @update:open="(v) => (!v && (editingCte = null))">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Sửa CTE: {{ editingCte?.code }}</DialogTitle>
          <DialogDescription>Cập nhật thông tin chuẩn cho bước truy xuất này.</DialogDescription>
        </DialogHeader>
        <div class="grid gap-4 py-4">
          <div class="space-y-2">
            <Label for="cteName">Tên sự kiện (tiếng Việt)</Label>
            <Input id="cteName" v-model="cteForm.name_vi" />
            <p v-if="cteForm.errors.name_vi" class="text-xs text-destructive">{{ cteForm.errors.name_vi }}</p>
          </div>
          <div class="space-y-2">
            <Label for="cteTcvn">Ghi chú TCVN</Label>
            <Input id="cteTcvn" v-model="cteForm.tcvn_note" placeholder="VD: TCVN 12850:2019 Điều 5.2" />
            <p v-if="cteForm.errors.tcvn_note" class="text-xs text-destructive">{{ cteForm.errors.tcvn_note }}</p>
          </div>
          <div class="flex items-center space-x-2 pt-2">
            <Checkbox id="is_required" :checked="cteForm.is_required" @update:checked="(v) => (cteForm.is_required = v)" />
            <Label for="is_required" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
              Bắt buộc theo tiêu chuẩn
            </Label>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="editingCte = null">Hủy</Button>
          <Button @click="submitEditCte" :disabled="cteForm.processing">Lưu thay đổi</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
</template>
