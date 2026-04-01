<script setup lang="ts">
import FinancialSimpleCard from "./FinancialSimpleCard.vue";
import { DollarSign } from "lucide-vue-next";
</script>

<template>
    <FinancialSimpleCard
        title="Inadimplência"
        value="R$ 1.500,00"
        percentage="+2.3%"
        percentageType="up"
        :isGreen="false"
        :icon="DollarSign"
        comparisonText="vs. mês passado"
    />
</template>
