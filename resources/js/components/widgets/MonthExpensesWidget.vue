<script setup lang="ts">
import { DollarSign } from "lucide-vue-next";
import FinancialSimpleCard from "./FinancialSimpleCard.vue";
</script>

<template>
    <FinancialSimpleCard
        title="Despesas do Mês"
        value="R$ 20.000,00"
        percentage="+7.5%"
        percentageType="up"
        :isGreen="false"
        :icon="DollarSign"
        comparisonText="vs. mês passado"
    />
</template>
