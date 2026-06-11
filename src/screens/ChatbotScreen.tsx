import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, FlatList, StyleSheet, KeyboardAvoidingView, Platform } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';

type NavProp = NativeStackNavigationProp<any>;

interface Message {
  id: string;
  text: string;
  sender: 'user' | 'bot';
}

export default function ChatbotScreen() {
  const navigation = useNavigation<NavProp>();
  const [inputText, setInputText] = useState('');
  const [messages, setMessages] = useState<Message[]>([
    { id: '1', text: "Bonjour ! Je suis le robot d'assistance CYNA. Comment puis-je vous aider ?", sender: 'bot' }
  ]);

  // LOGIQUE DE RÉPONSE BASÉE SUR LES DONNÉES SQL (chat_logs)
  const getBotResponse = (userMessage: string): string => {
    const msg = userMessage.toLowerCase();
    
    if (msg.includes('abonnement') || msg.includes('modifier')) {
      return "Vous pouvez gérer vos abonnements depuis votre espace compte → 'Mes abonnements'. La résiliation prend effet à la fin de la période en cours, sans frais supplémentaires.";
    } else if (msg.includes('resilier') || msg.includes('annuler')) {
      return "Vous pouvez gérer vos abonnements depuis votre espace compte → 'Mes abonnements'. La résiliation prend effet à la fin de la période en cours, sans frais supplémentaires.";
    } else if (msg.includes('essaie') || msg.includes('test') || msg.includes('gratuit')) {
      return "Certains de nos services proposent une période d'essai gratuite. Consultez les pages produits de notre catalogue pour voir les offres d'essai disponibles.";
    } else if (msg.includes('admin') || msg.includes('administrateur')) {
      return "Je ne suis pas sûr de comprendre votre demande. Pour une assistance personnalisée, n'hésitez pas à utiliser le formulaire de contact ou à nous écrire à contact@cyna-it.fr.";
    } else if (msg.includes('prix') || msg.includes('tarif') || msg.includes('coût')) {
      return "Les tarifs dépendent du service choisi (SOC, EDR, XDR) et du nombre de postes. Vous pouvez consulter notre catalogue pour voir toutes les offres.";
    }
    
    return "Je ne suis pas sûr de comprendre votre demande. Pour une assistance personnalisée, n'hésitez pas à utiliser le formulaire de contact ou à nous écrire à contact@cyna-it.fr.";
  };

  const sendMessage = () => {
    if (!inputText.trim()) return;

    const userMsg: Message = {
      id: Date.now().toString(),
      text: inputText,
      sender: 'user'
    };

    setMessages(prev => [...prev, userMsg]);
    setInputText('');

    // Simule le temps de réflexion du bot (1 seconde)
    setTimeout(() => {
      const botResponse: Message = {
        id: (Date.now() + 1).toString(),
        text: getBotResponse(userMsg.text),
        sender: 'bot'
      };
      setMessages(prev => [...prev, botResponse]);
    }, 1000);
  };

  const renderMessage = ({ item }: { item: Message }) => (
    <View style={[styles.messageBubble, item.sender === 'user' ? styles.userBubble : styles.botBubble]}>
      <Text style={[styles.messageText, item.sender === 'user' && styles.userText]}>{item.text}</Text>
    </View>
  );

  return (
    <View style={styles.container}>
      {/* Header du chat */}
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}>
          <Ionicons name="arrow-back" size={24} color="#fff" />
        </TouchableOpacity>
        <View style={{ marginLeft: 15 }}>
          <Text style={styles.headerTitle}>Assistant CYNA</Text>
          <Text style={styles.headerStatus}>En ligne</Text>
        </View>
        <View style={[styles.onlineDot, { marginLeft: 10 }]} />
      </View>

      {/* Zone de messages */}
      <FlatList
        data={messages}
        keyExtractor={item => item.id}
        renderItem={renderMessage}
        contentContainerStyle={styles.messagesList}
        inverted={false}
      />

      {/* Zone de saisie */}
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={styles.inputContainer}>
        <TextInput
          style={styles.input}
          value={inputText}
          onChangeText={setInputText}
          placeholder="Écrivez votre message..."
          placeholderTextColor="#aaa"
          multiline
        />
        <TouchableOpacity style={styles.sendButton} onPress={sendMessage}>
          <Ionicons name="send" size={20} color="#fff" />
        </TouchableOpacity>
      </KeyboardAvoidingView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F5F7FA' },
  header: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#0056b3', padding: 20, paddingTop: 25 },
  headerTitle: { color: '#fff', fontSize: 18, fontWeight: 'bold' },
  headerStatus: { color: 'rgba(255,255,255,0.8)', fontSize: 12 },
  onlineDot: { width: 10, height: 10, borderRadius: 5, backgroundColor: '#2ecc71' },
  messagesList: { padding: 15, flexGrow: 1, justifyContent: 'flex-end' },
  messageBubble: { maxWidth: '80%', padding: 15, borderRadius: 15, marginBottom: 10 },
  botBubble: { backgroundColor: '#fff', alignSelf: 'flex-start', borderBottomLeftRadius: 5, shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 4, elevation: 1 },
  userBubble: { backgroundColor: '#0056b3', alignSelf: 'flex-end', borderBottomRightRadius: 5 },
  messageText: { fontSize: 15, color: '#2C3E50', lineHeight: 22 },
  userText: { color: '#fff' },
  inputContainer: { flexDirection: 'row', backgroundColor: '#fff', padding: 15, borderTopWidth: 1, borderColor: '#eee', alignItems: 'flex-end' },
  input: { flex: 1, backgroundColor: '#f0f2f5', borderRadius: 20, paddingHorizontal: 15, paddingVertical: 10, fontSize: 16, maxHeight: 100, color: '#2C3E50' },
  sendButton: { backgroundColor: '#0056b3', width: 45, height: 45, borderRadius: 22, justifyContent: 'center', alignItems: 'center', marginLeft: 10 },
});